<?php

namespace Tests\Feature\Tenant\Specimen;

use Tests\Support\Tenant\SpecimenManagementTestCase;

class TenantSpecimenCollectionTest extends SpecimenManagementTestCase
{
    public function test_tenant_user_can_collect_multiple_specimens_for_an_order(): void
    {
        $context = $this
            ->authenticatedSpecimenContext(
                'specimen-collection'
            );

        $patient = $this->createPatient(
            $context['tenant'],
            [
                'first_name' => 'Ada',
                'last_name' => 'Okafor',
            ]
        );

        $order = $this->createPendingOrderWithItems(
            $context['tenant'],
            $patient,
            2
        );

        $items = $order->items
            ->values();

        $response = $this
            ->withHeaders(
                $context['headers']
            )
            ->postJson(
                '/api/laboratory-orders/'
                .$order->uuid
                .'/specimens',
                [
                    'specimens' => [
                        [
                            'specimen_type' => (
                                'Whole Blood'
                            ),
                            'container_type' => (
                                'EDTA Tube'
                            ),
                            'collection_site' => (
                                'Left Arm'
                            ),
                            'volume' => 4.5,
                            'volume_unit' => 'mL',
                            'notes' => (
                                'Collected without difficulty.'
                            ),
                            'order_item_uuids' => [
                                $items[0]->uuid,
                                $items[1]->uuid,
                            ],
                        ],
                        [
                            'specimen_type' => 'Urine',
                            'container_type' => (
                                'Sterile Container'
                            ),
                            'volume' => 20,
                            'volume_unit' => 'mL',
                            'order_item_uuids' => [
                                $items[1]->uuid,
                            ],
                        ],
                    ],
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Specimens collected successfully.'
            )
            ->assertJsonPath(
                'data.uuid',
                $order->uuid
            )
            ->assertJsonPath(
                'data.status',
                'sample_collected'
            )
            ->assertJsonCount(
                2,
                'data.specimens'
            );

        $responseSpecimens = collect(
            $response->json(
                'data.specimens'
            )
        )->keyBy('specimen_type');

        self::assertTrue(
            $responseSpecimens->has(
                'Whole Blood'
            )
        );

        self::assertTrue(
            $responseSpecimens->has(
                'Urine'
            )
        );

        self::assertCount(
            2,
            $responseSpecimens[
                'Whole Blood'
            ]['order_item_uuids']
        );

        self::assertCount(
            1,
            $responseSpecimens[
                'Urine'
            ]['order_item_uuids']
        );

        self::assertNotNull(
            $responseSpecimens[
                'Whole Blood'
            ]['collected_by']
        );

        $storedOrder = $this
            ->findLaboratoryOrder(
                $context['tenant'],
                $order->uuid
            );

        self::assertNotNull($storedOrder);
        self::assertSame(
            'sample_collected',
            $storedOrder->status
        );
        self::assertNotNull(
            $storedOrder->sample_collected_at
        );

        $storedSpecimens = $this
            ->specimensForOrder(
                $context['tenant'],
                $order->uuid
            );

        self::assertCount(
            2,
            $storedSpecimens
        );

        self::assertCount(
            2,
            $storedSpecimens
                ->pluck('accession_number')
                ->unique()
        );

        self::assertSame(
            3,
            $this->specimenItemLinkCount(
                $context['tenant'],
                $order->uuid
            )
        );

        foreach (
            $storedSpecimens as $specimen
        ) {
            self::assertSame(
                'collected',
                $specimen->status
            );

            self::assertNotNull(
                $specimen->collected_at
            );

            self::assertNotNull(
                $specimen->collected_by
            );

            self::assertStringStartsWith(
                'SP-',
                $specimen->accession_number
            );
        }
    }
}
