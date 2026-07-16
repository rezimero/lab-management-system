<?php

namespace Tests\Feature\Tenant\Specimen;

use App\Models\LaboratoryOrder;
use Tests\Support\Tenant\SpecimenStatusTestCase;

class TenantSpecimenReplacementValidationTest extends SpecimenStatusTestCase
{
    public function test_only_rejected_specimen_can_be_replaced(): void
    {
        $context = $this->authenticatedSpecimenContext(
            'replacement-status'
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
            $order
        );

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/specimens/'
                .$source->uuid
                .'/replacement',
                []
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'specimen',
            ]);

        $this->assertSame(
            1,
            $this->specimenCount(
                $context['tenant'],
                $order->uuid
            )
        );
    }

    public function test_rejected_specimen_cannot_have_two_direct_replacements(): void
    {
        $context = $this->authenticatedSpecimenContext(
            'replacement-duplicate'
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
                    'Clotted specimen'
                ),
            ]
        );

        $url = '/api/specimens/'
            .$source->uuid
            .'/replacement';

        $this
            ->withHeaders($context['headers'])
            ->postJson($url, [])
            ->assertCreated();

        $this
            ->withHeaders($context['headers'])
            ->postJson($url, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'specimen',
            ]);

        $this->assertSame(
            2,
            $this->specimenCount(
                $context['tenant'],
                $order->uuid
            )
        );
    }

    public function test_specimen_cannot_be_replaced_after_processing_begins(): void
    {
        $context = $this->authenticatedSpecimenContext(
            'replacement-processing'
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
                    'Insufficient volume'
                ),
            ]
        );

        $this->runInTenant(
            $context['tenant'],
            static function () use ($order): void {
                LaboratoryOrder::query()
                    ->where('uuid', $order->uuid)
                    ->update([
                        'status' => 'in_progress',
                    ]);
            }
        );

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/specimens/'
                .$source->uuid
                .'/replacement',
                []
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'specimen',
            ]);

        $this->assertSame(
            1,
            $this->specimenCount(
                $context['tenant'],
                $order->uuid
            )
        );
    }
}
