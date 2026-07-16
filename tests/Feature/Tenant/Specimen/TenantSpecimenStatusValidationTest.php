<?php

namespace Tests\Feature\Tenant\Specimen;

use Tests\Support\Tenant\SpecimenStatusTestCase;

class TenantSpecimenStatusValidationTest extends SpecimenStatusTestCase
{
    public function test_status_must_be_supported(): void
    {
        $context = $this->authenticatedSpecimenContext(
            'specimen-invalid-status'
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

        $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/specimens/'
                .$specimen->uuid
                .'/status',
                [
                    'status' => 'collected',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ]);

        $stored = $this->storedSpecimen(
            $context['tenant'],
            $specimen->uuid
        );

        $this->assertSame(
            'collected',
            $stored->status
        );
    }

    public function test_rejection_requires_a_reason(): void
    {
        $context = $this->authenticatedSpecimenContext(
            'specimen-reason-required'
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

        $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/specimens/'
                .$specimen->uuid
                .'/status',
                [
                    'status' => 'rejected',
                    'rejection_reason' => '   ',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'rejection_reason',
            ]);

        $stored = $this->storedSpecimen(
            $context['tenant'],
            $specimen->uuid
        );

        $this->assertSame(
            'collected',
            $stored->status
        );

        $this->assertNull(
            $stored->rejected_at
        );
    }

    public function test_rejected_specimen_is_terminal(): void
    {
        $context = $this->authenticatedSpecimenContext(
            'specimen-rejected-terminal'
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
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => (
                    'Insufficient volume'
                ),
            ]
        );

        $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/specimens/'
                .$specimen->uuid
                .'/status',
                [
                    'status' => 'received',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ]);

        $stored = $this->storedSpecimen(
            $context['tenant'],
            $specimen->uuid
        );

        $this->assertSame(
            'rejected',
            $stored->status
        );

        $this->assertSame(
            'Insufficient volume',
            $stored->rejection_reason
        );
    }

    public function test_specimen_cannot_repeat_its_current_status(): void
    {
        $context = $this->authenticatedSpecimenContext(
            'specimen-repeat-status'
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

        $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/specimens/'
                .$specimen->uuid
                .'/status',
                [
                    'status' => 'received',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ]);

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
    }
}