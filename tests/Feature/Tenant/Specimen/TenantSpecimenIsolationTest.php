<?php

namespace Tests\Feature\Tenant\Specimen;

use Tests\Support\Tenant\SpecimenManagementTestCase;

class TenantSpecimenIsolationTest extends SpecimenManagementTestCase
{
    public function test_tenant_cannot_collect_specimens_for_another_tenants_order(): void
    {
        $context = $this
            ->authenticatedSpecimenContext(
                'specimen-owner'
            );

        $foreignContext = $this
            ->authenticatedSpecimenContext(
                'specimen-foreign'
            );

        $foreignPatient = $this
            ->createPatient(
                $foreignContext['tenant']
            );

        $foreignOrder = $this
            ->createPendingOrderWithItems(
                $foreignContext['tenant'],
                $foreignPatient,
                1
            );

        $this->withHeaders(
            $context['headers']
        )
            ->postJson(
                '/api/laboratory-orders/'
                .$foreignOrder->uuid
                .'/specimens',
                [
                    'specimens' => [
                        [
                            'specimen_type' => (
                                'Whole Blood'
                            ),
                            'order_item_uuids' => [
                                $foreignOrder
                                    ->items[0]
                                    ->uuid,
                            ],
                        ],
                    ],
                ]
            )
            ->assertNotFound();

        self::assertSame(
            0,
            $this->specimenCount(
                $foreignContext['tenant'],
                $foreignOrder->uuid
            )
        );

        $storedOrder = $this
            ->findLaboratoryOrder(
                $foreignContext['tenant'],
                $foreignOrder->uuid
            );

        self::assertNotNull($storedOrder);
        self::assertSame(
            'pending',
            $storedOrder->status
        );
        self::assertNull(
            $storedOrder->sample_collected_at
        );
    }
}
