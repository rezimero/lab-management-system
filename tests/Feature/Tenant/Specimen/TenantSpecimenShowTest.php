<?php

namespace Tests\Feature\Tenant\Specimen;

use Tests\Support\Tenant\SpecimenStatusTestCase;

class TenantSpecimenShowTest extends SpecimenStatusTestCase
{
    public function test_show_uses_uuid_and_is_tenant_scoped(): void
    {
        $firstContext = (
            $this->authenticatedSpecimenContext(
                'specimen-show-first'
            )
        );

        $secondContext = (
            $this->authenticatedSpecimenContext(
                'specimen-show-second'
            )
        );

        $firstPatient = $this->createPatient(
            $firstContext['tenant'],
            [
                'first_name' => 'Show',
                'last_name' => 'Patient',
            ]
        );

        $firstOrder = (
            $this->createPendingOrderWithItems(
                $firstContext['tenant'],
                $firstPatient
            )
        );

        $firstSpecimen = (
            $this->createCollectedSpecimen(
                $firstContext['tenant'],
                $firstOrder,
                null,
                [
                    'accession_number' => (
                        'SP-SHOW-FIRST'
                    ),
                    'specimen_type' => 'Plasma',
                ]
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
            ->getJson(
                '/api/specimens/'
                .$firstSpecimen->uuid
            )
            ->assertOk()
            ->assertJsonPath(
                'data.uuid',
                $firstSpecimen->uuid
            )
            ->assertJsonPath(
                'data.accession_number',
                'SP-SHOW-FIRST'
            )
            ->assertJsonPath(
                'data.specimen_type',
                'Plasma'
            )
            ->assertJsonPath(
                'data.order.uuid',
                $firstOrder->uuid
            )
            ->assertJsonPath(
                'data.order.patient.first_name',
                'Show'
            )
            ->assertJsonCount(
                2,
                'data.order_item_uuids'
            )
            ->assertJsonCount(
                2,
                'data.order_items'
            );

        $this
            ->withHeaders(
                $firstContext['headers']
            )
            ->getJson(
                '/api/specimens/'
                .$secondSpecimen->uuid
            )
            ->assertNotFound();
    }
}