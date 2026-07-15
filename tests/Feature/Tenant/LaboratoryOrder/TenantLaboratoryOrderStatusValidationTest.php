<?php

namespace Tests\Feature\Tenant\LaboratoryOrder;

use Tests\Support\Tenant\LaboratoryOrderManagementTestCase;

class TenantLaboratoryOrderStatusValidationTest extends LaboratoryOrderManagementTestCase
{
    public function test_order_cannot_skip_required_status_stages(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-status-skip'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $order = $this->createLaboratoryOrder(
            $context['tenant'],
            $patient
        );

        $this->withHeaders(
            $context['headers']
        )
            ->patchJson(
                '/api/laboratory-orders/'
                .$order->uuid
                .'/status',
                [
                    'status' => 'completed',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ])
            ->assertJsonPath(
                'errors.status.0',
                'Laboratory order cannot move from pending to completed.'
            );

        $storedOrder = $this->findLaboratoryOrder(
            $context['tenant'],
            $order->uuid
        );

        self::assertNotNull($storedOrder);
        self::assertSame(
            'pending',
            $storedOrder->status
        );
        self::assertNull(
            $storedOrder->completed_at
        );
    }

    public function test_terminal_orders_cannot_change_status(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-status-terminal'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $completedOrder = $this->createLaboratoryOrder(
            $context['tenant'],
            $patient,
            [
                'status' => 'completed',
                'completed_at' => now(),
            ]
        );

        $cancelledOrder = $this->createLaboratoryOrder(
            $context['tenant'],
            $patient,
            [
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]
        );

        $this->withHeaders(
            $context['headers']
        )
            ->patchJson(
                '/api/laboratory-orders/'
                .$completedOrder->uuid
                .'/status',
                [
                    'status' => 'pending',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ]);

        $this->withHeaders(
            $context['headers']
        )
            ->patchJson(
                '/api/laboratory-orders/'
                .$cancelledOrder->uuid
                .'/status',
                [
                    'status' => 'in_progress',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ]);

        self::assertSame(
            'completed',
            $this->findLaboratoryOrder(
                $context['tenant'],
                $completedOrder->uuid
            )?->status
        );

        self::assertSame(
            'cancelled',
            $this->findLaboratoryOrder(
                $context['tenant'],
                $cancelledOrder->uuid
            )?->status
        );
    }

    public function test_status_must_be_a_supported_value(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-status-value'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $order = $this->createLaboratoryOrder(
            $context['tenant'],
            $patient
        );

        $this->withHeaders(
            $context['headers']
        )
            ->patchJson(
                '/api/laboratory-orders/'
                .$order->uuid
                .'/status',
                [
                    'status' => 'processing',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ])
            ->assertJsonPath(
                'errors.status.0',
                'The selected laboratory order status is invalid.'
            );

        self::assertSame(
            'pending',
            $this->findLaboratoryOrder(
                $context['tenant'],
                $order->uuid
            )?->status
        );
    }
}
