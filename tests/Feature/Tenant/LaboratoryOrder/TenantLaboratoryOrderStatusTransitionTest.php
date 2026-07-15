<?php

namespace Tests\Feature\Tenant\LaboratoryOrder;

use Tests\Support\Tenant\LaboratoryOrderManagementTestCase;

class TenantLaboratoryOrderStatusTransitionTest extends LaboratoryOrderManagementTestCase
{
    public function test_order_follows_the_valid_processing_workflow(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-status-workflow'
            );

        $patient = $this->createPatient(
            $context['tenant'],
            [
                'first_name' => 'Grace',
                'last_name' => 'Okoro',
            ]
        );

        $order = $this->createLaboratoryOrder(
            $context['tenant'],
            $patient
        );

        $endpoint = (
            '/api/laboratory-orders/'
            .$order->uuid
            .'/status'
        );

        $this->withHeaders(
            $context['headers']
        )
            ->patchJson(
                $endpoint,
                [
                    'status' => 'sample_collected',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Laboratory order status updated successfully.'
            )
            ->assertJsonPath(
                'data.uuid',
                $order->uuid
            )
            ->assertJsonPath(
                'data.status',
                'sample_collected'
            );

        $storedOrder = $this->findLaboratoryOrder(
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
                $endpoint,
                [
                    'status' => 'in_progress',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'in_progress'
            );

        $storedOrder = $this->findLaboratoryOrder(
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
                $endpoint,
                [
                    'status' => 'completed',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'completed'
            );

        $storedOrder = $this->findLaboratoryOrder(
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
            ->authenticatedLaboratoryOrderContext(
                'order-status-cancellation'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $pendingOrder = $this->createLaboratoryOrder(
            $context['tenant'],
            $patient
        );

        $collectedOrder = $this->createLaboratoryOrder(
            $context['tenant'],
            $patient,
            [
                'status' => 'sample_collected',
                'sample_collected_at' => now(),
            ]
        );

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
