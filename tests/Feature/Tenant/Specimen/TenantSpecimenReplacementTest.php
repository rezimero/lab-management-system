<?php

namespace Tests\Feature\Tenant\Specimen;

use App\Models\Specimen;
use Tests\Support\Tenant\SpecimenStatusTestCase;

class TenantSpecimenReplacementTest extends SpecimenStatusTestCase
{
    public function test_rejected_specimen_can_be_replaced(): void
    {
        $context = $this->authenticatedSpecimenContext(
            'specimen-replacement'
        );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $order = $this->createPendingOrderWithItems(
            $context['tenant'],
            $patient
        );

        $source = $this->createCollectedSpecimen(
            $context['tenant'],
            $order,
            null,
            [
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => (
                    'Incorrect container'
                ),
            ]
        );

        $response = $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/specimens/'
                .$source->uuid
                .'/replacement',
                [
                    'container_type' => (
                        'Sodium Citrate Tube'
                    ),
                    'volume' => 4.5,
                    'volume_unit' => 'mL',
                    'notes' => (
                        'Replacement collection'
                    ),
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Replacement specimen collected successfully.'
            )
            ->assertJsonPath(
                'data.status',
                'collected'
            )
            ->assertJsonPath(
                'data.replaces_specimen.uuid',
                $source->uuid
            )
            ->assertJsonPath(
                'data.container_type',
                'Sodium Citrate Tube'
            )
            ->assertJsonPath(
                'data.volume',
                '4.50'
            )
            ->assertJsonCount(
                2,
                'data.order_item_uuids'
            );

        $replacementUuid = $response->json(
            'data.uuid'
        );

        $this->assertIsString($replacementUuid);

        $replacement = $this->runInTenant(
            $context['tenant'],
            static fn (): Specimen => (
                Specimen::query()
                    ->with([
                        'replaces',
                        'orderItems',
                    ])
                    ->where(
                        'uuid',
                        $replacementUuid
                    )
                    ->firstOrFail()
            )
        );

        $this->assertSame(
            'collected',
            $replacement->status
        );

        $this->assertSame(
            $source->uuid,
            $replacement->replaces?->uuid
        );

        $this->assertCount(
            2,
            $replacement->orderItems
        );

        $this->assertNotSame(
            $source->accession_number,
            $replacement->accession_number
        );

        $storedSource = $this->runInTenant(
            $context['tenant'],
            static fn (): Specimen => (
                Specimen::query()
                    ->with('replacement')
                    ->where(
                        'uuid',
                        $source->uuid
                    )
                    ->firstOrFail()
            )
        );

        $this->assertSame(
            $replacementUuid,
            $storedSource->replacement?->uuid
        );
    }
}
