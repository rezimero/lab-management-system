<?php

namespace Tests\Feature\Tenant\LaboratoryOrder;

use App\Models\LaboratoryOrderItem;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\Support\Tenant\LaboratoryOrderManagementTestCase;

class TenantLaboratoryOrderTransactionTest extends LaboratoryOrderManagementTestCase
{
    public function test_item_failure_rolls_back_the_complete_order(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-rollback'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $labTest = $this->createLabTest(
            $context['tenant']
        );

        $event = (
            'eloquent.creating: '
            .LaboratoryOrderItem::class
        );

        Event::listen(
            $event,
            static function (): void {
                throw new RuntimeException(
                    'Forced order item failure.'
                );
            }
        );

        try {
            $this->withHeaders(
                $context['headers']
            )
                ->postJson(
                    '/api/laboratory-orders',
                    [
                        'patient_id' => (
                            $patient->id
                        ),
                        'items' => [
                            [
                                'lab_test_id' => (
                                    $labTest->id
                                ),
                            ],
                        ],
                    ]
                )
                ->assertStatus(500);
        } finally {
            Event::forget($event);
        }

        $this->assertSame(
            0,
            $this->tenantTableCount(
                $context['tenant'],
                'laboratory_orders'
            )
        );

        $this->assertSame(
            0,
            $this->tenantTableCount(
                $context['tenant'],
                'laboratory_order_items'
            )
        );
    }
}
