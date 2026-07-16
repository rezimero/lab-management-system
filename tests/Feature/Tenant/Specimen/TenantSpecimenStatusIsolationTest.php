<?php

namespace Tests\Feature\Tenant\Specimen;

use Tests\Support\Tenant\SpecimenStatusTestCase;

class TenantSpecimenStatusIsolationTest extends SpecimenStatusTestCase
{
    public function test_tenant_cannot_update_another_tenants_specimen(): void
    {
        $firstContext = (
            $this->authenticatedSpecimenContext(
                'specimen-status-first'
            )
        );

        $secondContext = (
            $this->authenticatedSpecimenContext(
                'specimen-status-second'
            )
        );

        $secondPatient = $this->createPatient(
            $secondContext['tenant']
        );

        $secondOrder = (
            $this->createPendingOrderWithItems(
                $secondContext['tenant'],
                $secondPatient
            )
        );

        $secondSpecimen = (
            $this->createCollectedSpecimen(
                $secondContext['tenant'],
                $secondOrder
            )
        );

        $this
            ->withHeaders(
                $firstContext['headers']
            )
            ->patchJson(
                '/api/specimens/'
                .$secondSpecimen->uuid
                .'/status',
                [
                    'status' => 'received',
                ]
            )
            ->assertNotFound();

        $stored = $this->storedSpecimen(
            $secondContext['tenant'],
            $secondSpecimen->uuid
        );

        $this->assertSame(
            'collected',
            $stored->status
        );

        $this->assertNull(
            $stored->received_at
        );
    }
}