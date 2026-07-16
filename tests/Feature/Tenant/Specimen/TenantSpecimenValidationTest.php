<?php

namespace Tests\Feature\Tenant\Specimen;

use Tests\Support\Tenant\SpecimenManagementTestCase;

class TenantSpecimenValidationTest extends SpecimenManagementTestCase
{
    public function test_every_active_order_item_must_be_assigned_to_a_specimen(): void
    {
        $context = $this
            ->authenticatedSpecimenContext(
                'specimen-coverage'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $order = $this->createPendingOrderWithItems(
            $context['tenant'],
            $patient,
            2
        );

        $this->withHeaders(
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
                            'order_item_uuids' => [
                                $order->items[0]->uuid,
                            ],
                        ],
                    ],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'specimens',
            ])
            ->assertJsonPath(
                'errors.specimens.0',
                'Every active order item must be assigned to a specimen.'
            );

        self::assertSame(
            0,
            $this->specimenCount(
                $context['tenant'],
                $order->uuid
            )
        );

        self::assertSame(
            'pending',
            $this->findLaboratoryOrder(
                $context['tenant'],
                $order->uuid
            )?->status
        );
    }

    public function test_specimen_cannot_reference_an_item_from_another_order(): void
    {
        $context = $this
            ->authenticatedSpecimenContext(
                'specimen-item-owner'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $firstOrder = $this
            ->createPendingOrderWithItems(
                $context['tenant'],
                $patient,
                1
            );

        $secondOrder = $this
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
                .$firstOrder->uuid
                .'/specimens',
                [
                    'specimens' => [
                        [
                            'specimen_type' => (
                                'Serum'
                            ),
                            'order_item_uuids' => [
                                $firstOrder
                                    ->items[0]
                                    ->uuid,
                                $secondOrder
                                    ->items[0]
                                    ->uuid,
                            ],
                        ],
                    ],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'specimens',
            ])
            ->assertJsonPath(
                'errors.specimens.0',
                'One or more selected order items do not belong to this order.'
            );

        self::assertSame(
            0,
            $this->specimenCount(
                $context['tenant'],
                $firstOrder->uuid
            )
        );

        self::assertSame(
            'pending',
            $this->findLaboratoryOrder(
                $context['tenant'],
                $firstOrder->uuid
            )?->status
        );
    }

    public function test_specimens_can_only_be_collected_for_a_pending_order(): void
    {
        $context = $this
            ->authenticatedSpecimenContext(
                'specimen-order-status'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $order = $this
            ->createPendingOrderWithItems(
                $context['tenant'],
                $patient,
                1
            );

        $this->runInTenant(
            $context['tenant'],
            function () use ($order): void {
                $order->forceFill([
                    'status' => 'in_progress',
                ])->save();
            }
        );

        $this->withHeaders(
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
                            'order_item_uuids' => [
                                $order->items[0]->uuid,
                            ],
                        ],
                    ],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ])
            ->assertJsonPath(
                'errors.status.0',
                'Specimens can only be collected for a pending order.'
            );

        self::assertSame(
            0,
            $this->specimenCount(
                $context['tenant'],
                $order->uuid
            )
        );
    }

    public function test_status_endpoint_cannot_fake_specimen_collection(): void
    {
        $context = $this
            ->authenticatedSpecimenContext(
                'specimen-status-protection'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $order = $this
            ->createPendingOrderWithItems(
                $context['tenant'],
                $patient,
                1
            );

        $this->withHeaders(
            $context['headers']
        )
            ->patchJson(
                '/api/laboratory-orders/'
                .$order->uuid
                .'/status',
                [
                    'status' => (
                        'sample_collected'
                    ),
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ])
            ->assertJsonPath(
                'errors.status.0',
                'Record the specimens before marking the order as sample collected.'
            );

        $storedOrder = $this
            ->findLaboratoryOrder(
                $context['tenant'],
                $order->uuid
            );

        self::assertNotNull($storedOrder);
        self::assertSame(
            'pending',
            $storedOrder->status
        );
        self::assertNull(
            $storedOrder->sample_collected_at
        );

        self::assertSame(
            0,
            $this->specimenCount(
                $context['tenant'],
                $order->uuid
            )
        );
    }
}
