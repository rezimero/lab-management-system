<?php

namespace Tests\Feature\Tenant\Specimen;

use Tests\Support\Tenant\SpecimenStatusTestCase;

class TenantSpecimenReplacementIsolationTest extends SpecimenStatusTestCase
{
    public function test_tenant_cannot_replace_another_tenants_specimen(): void
    {
        $firstContext = (
            $this->authenticatedSpecimenContext(
                'replacement-first'
            )
        );

        $secondContext = (
            $this->authenticatedSpecimenContext(
                'replacement-second'
            )
        );

        $patient = $this->createPatient(
            $secondContext['tenant']
        );

        $order = $this->createPendingOrderWithItems(
            $secondContext['tenant'],
            $patient
        );

        $source = $this->createCollectedSpecimen(
            $secondContext['tenant'],
            $order,
            null,
            [
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => (
                    'Incorrect labelling'
                ),
            ]
        );

        $this
            ->withHeaders(
                $firstContext['headers']
            )
            ->postJson(
                '/api/specimens/'
                .$source->uuid
                .'/replacement',
                []
            )
            ->assertNotFound();

        $this->assertSame(
            1,
            $this->specimenCount(
                $secondContext['tenant'],
                $order->uuid
            )
        );
    }
}
