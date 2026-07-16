<?php

namespace Tests\Feature\Tenant\Specimen;

use Tests\Support\Tenant\SpecimenStatusTestCase;

class TenantSpecimenReceivingTest extends SpecimenStatusTestCase
{
    public function test_collected_specimen_can_be_received(): void
    {
        $context = $this->authenticatedSpecimenContext(
            'specimen-receive'
        );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $order = $this->createPendingOrderWithItems(
            $context['tenant'],
            $patient
        );

        $specimen = $this->createCollectedSpecimen(
            $context['tenant'],
            $order
        );

        $response = $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/specimens/'
                .$specimen->uuid
                .'/status',
                [
                    'status' => 'received',
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Specimen status updated successfully.'
            )
            ->assertJsonPath(
                'data.uuid',
                $specimen->uuid
            )
            ->assertJsonPath(
                'data.status',
                'received'
            )
            ->assertJsonPath(
                'data.rejected_at',
                null
            )
            ->assertJsonPath(
                'data.rejection_reason',
                null
            );

        $this->assertNotNull(
            $response->json(
                'data.received_at'
            )
        );

        $stored = $this->storedSpecimen(
            $context['tenant'],
            $specimen->uuid
        );

        $this->assertSame(
            'received',
            $stored->status
        );

        $this->assertNotNull(
            $stored->received_at
        );

        $this->assertNull(
            $stored->rejected_at
        );

        $this->assertNull(
            $stored->rejection_reason
        );
    }

    public function test_collected_specimen_can_be_rejected(): void
    {
        $context = $this->authenticatedSpecimenContext(
            'specimen-reject-collected'
        );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $order = $this->createPendingOrderWithItems(
            $context['tenant'],
            $patient
        );

        $specimen = $this->createCollectedSpecimen(
            $context['tenant'],
            $order
        );

        $response = $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/specimens/'
                .$specimen->uuid
                .'/status',
                [
                    'status' => 'rejected',
                    'rejection_reason' => (
                        '  Incorrect container type  '
                    ),
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'rejected'
            )
            ->assertJsonPath(
                'data.received_at',
                null
            )
            ->assertJsonPath(
                'data.rejection_reason',
                'Incorrect container type'
            );

        $this->assertNotNull(
            $response->json(
                'data.rejected_at'
            )
        );

        $stored = $this->storedSpecimen(
            $context['tenant'],
            $specimen->uuid
        );

        $this->assertSame(
            'rejected',
            $stored->status
        );

        $this->assertNull(
            $stored->received_at
        );

        $this->assertNotNull(
            $stored->rejected_at
        );

        $this->assertSame(
            'Incorrect container type',
            $stored->rejection_reason
        );
    }

    public function test_received_specimen_can_be_rejected(): void
    {
        $context = $this->authenticatedSpecimenContext(
            'specimen-reject-received'
        );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $order = $this->createPendingOrderWithItems(
            $context['tenant'],
            $patient
        );

        $specimen = $this->createCollectedSpecimen(
            $context['tenant'],
            $order,
            null,
            [
                'status' => 'received',
                'received_at' => now(),
            ]
        );

        $response = $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/specimens/'
                .$specimen->uuid
                .'/status',
                [
                    'status' => 'rejected',
                    'rejection_reason' => (
                        'Specimen was haemolysed'
                    ),
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'rejected'
            )
            ->assertJsonPath(
                'data.rejection_reason',
                'Specimen was haemolysed'
            );

        $this->assertNotNull(
            $response->json(
                'data.received_at'
            )
        );

        $this->assertNotNull(
            $response->json(
                'data.rejected_at'
            )
        );

        $stored = $this->storedSpecimen(
            $context['tenant'],
            $specimen->uuid
        );

        $this->assertSame(
            'rejected',
            $stored->status
        );

        $this->assertNotNull(
            $stored->received_at
        );

        $this->assertNotNull(
            $stored->rejected_at
        );
    }
}