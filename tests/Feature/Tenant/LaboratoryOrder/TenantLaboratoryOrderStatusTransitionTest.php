<?php

namespace Tests\Feature\Tenant\LaboratoryOrder;

use Tests\Support\Tenant\SpecimenManagementTestCase;

class TenantLaboratoryOrderStatusTransitionTest extends SpecimenManagementTestCase
{
    public function test_order_follows_the_valid_processing_workflow(): void
    {
        $context = $this
            ->authenticatedSpecimenContext(
                'order-status-workflow'
            );

        $patient = $this->createPatient(
            $context['tenant'],
            [
                'first_name' => 'Grace',
                'last_name' => 'Okoro',
            ]
        );

        $order = $this
            ->createPendingOrderWithItems(
                $context['tenant'],
                $patient,
                1
            );

        $endpoint = (
            '/api/laboratory-orders/'
            .$order->uuid
        );

        $this->withHeaders(
            $context['headers']
        )
            ->postJson(
                $endpoint.'/specimens',
                [
                    'specimens' => [
                        [
                            'specimen_type' => (
                                'Whole Blood'
                            ),
                            'container_type' => (
                                'EDTA Tube'
                            ),
                            'order_item_uuids' => [
                                $order->items[0]->uuid,
                            ],
                        ],
                    ],
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'data.status',
                'sample_collected'
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
        self::assertNull(
            $storedOrder->completed_at
        );

        $this->withHeaders(
            $context['headers']
        )
            ->patchJson(
                $endpoint.'/status',
                [
                    'status' => 'in_progress',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'in_progress'
            );

        $storedOrder = $this
            ->findLaboratoryOrder(
                $context['tenant'],
                $order->uuid
            );

        self::assertNotNull($storedOrder);
        self::assertSame(
            'in_progress',
            $storedOrder->status
        );
        self::assertNull(
            $storedOrder->completed_at
        );

        $this->withHeaders(
            $context['headers']
        )
            ->patchJson(
                $endpoint.'/status',
                [
                    'status' => 'completed',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'completed'
            );

        $storedOrder = $this
            ->findLaboratoryOrder(
                $context['tenant'],
                $order->uuid
            );

        self::assertNotNull($storedOrder);
        self::assertSame(
            'completed',
            $storedOrder->status
        );
        self::assertNotNull(
            $storedOrder->completed_at
        );
        self::assertNull(
            $storedOrder->cancelled_at
        );
    }

    public function test_order_can_be_cancelled_from_permitted_stages(): void
    {
        $context = $this
            ->authenticatedSpecimenContext(
                'order-status-cancellation'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $pendingOrder = $this
            ->createPendingOrderWithItems(
                $context['tenant'],
                $patient,
                1
            );

        $collectedOrder = $this
            ->createPendingOrderWithItems(
                $context['tenant'],
                $patient,
                1
            );

        $this->withHeaders(
            $context['headers']
        )
            ->postJson(
                '/api/laboratory-orders/'
                .$collectedOrder->uuid
                .'/specimens',
                [
                    'specimens' => [
                        [
                            'specimen_type' => (
                                'Serum'
                            ),
                            'order_item_uuids' => [
                                $collectedOrder
                                    ->items[0]
                                    ->uuid,
                            ],
                        ],
                    ],
                ]
            )
            ->assertCreated();

        foreach (
            [
                $pendingOrder,
                $collectedOrder,
            ] as $order
        ) {
            $this->withHeaders(
                $context['headers']
            )
                ->patchJson(
                    '/api/laboratory-orders/'
                    .$order->uuid
                    .'/status',
                    [
                        'status' => 'cancelled',
                    ]
                )
                ->assertOk()
                ->assertJsonPath(
                    'data.status',
                    'cancelled'
                );

            $storedOrder = $this
                ->findLaboratoryOrder(
                    $context['tenant'],
                    $order->uuid
                );

            self::assertNotNull($storedOrder);
            self::assertSame(
                'cancelled',
                $storedOrder->status
            );
            self::assertNotNull(
                $storedOrder->cancelled_at
            );
        }
    }
}
