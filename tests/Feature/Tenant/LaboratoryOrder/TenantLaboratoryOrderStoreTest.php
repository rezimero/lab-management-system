<?php

namespace Tests\Feature\Tenant\LaboratoryOrder;

use Tests\Support\Tenant\LaboratoryOrderManagementTestCase;

class TenantLaboratoryOrderStoreTest extends LaboratoryOrderManagementTestCase
{
    public function test_tenant_user_can_create_an_order_with_catalogue_snapshots(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-store'
            );

        $patient = $this->createPatient(
            $context['tenant'],
            [
                'first_name' => 'Ada',
                'last_name' => 'Okafor',
            ]
        );

        $bloodCount = $this->createLabTest(
            $context['tenant'],
            [
                'name' => 'Full Blood Count',
                'category' => 'Haematology',
                'price' => 12500.50,
                'turnaround_time_hours' => 4,
                'parameters' => [
                    [
                        'name' => 'Haemoglobin',
                        'unit' => 'g/dL',
                    ],
                ],
            ]
        );

        $glucose = $this->createLabTest(
            $context['tenant'],
            [
                'name' => 'Fasting Blood Glucose',
                'category' => 'Chemistry',
                'price' => 2500,
                'turnaround_time_hours' => 2,
                'parameters' => [
                    [
                        'name' => 'Glucose',
                        'unit' => 'mg/dL',
                    ],
                ],
            ]
        );

        $response = $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/laboratory-orders',
                [
                    'patient_id' => $patient->id,
                    'referring_clinician' => (
                        '  Dr. Musa Bello  '
                    ),
                    'priority' => 'urgent',
                    'clinical_notes' => (
                        '  Persistent fatigue  '
                    ),
                    'items' => [
                        [
                            'lab_test_id' => (
                                $bloodCount->id
                            ),
                        ],
                        [
                            'lab_test_id' => (
                                $glucose->id
                            ),
                        ],
                    ],
                ]
            );

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Laboratory order created successfully.'
            )
            ->assertJsonPath(
                'data.patient.id',
                $patient->id
            )
            ->assertJsonPath(
                'data.patient.full_name',
                'Ada Okafor'
            )
            ->assertJsonPath(
                'data.referring_clinician',
                'Dr. Musa Bello'
            )
            ->assertJsonPath(
                'data.priority',
                'urgent'
            )
            ->assertJsonPath(
                'data.status',
                'pending'
            )
            ->assertJsonPath(
                'data.clinical_notes',
                'Persistent fatigue'
            )
            ->assertJsonPath(
                'data.total_amount',
                '15000.50'
            )
            ->assertJsonCount(
                2,
                'data.items'
            )
            ->assertJsonPath(
                'data.items.0.test_name',
                'Full Blood Count'
            )
            ->assertJsonPath(
                'data.items.0.category',
                'Haematology'
            )
            ->assertJsonPath(
                'data.items.0.price',
                '12500.50'
            )
            ->assertJsonPath(
                'data.items.1.test_name',
                'Fasting Blood Glucose'
            )
            ->assertJsonPath(
                'data.items.1.price',
                '2500.00'
            );

        $uuid = $response->json(
            'data.uuid'
        );

        $orderNumber = $response->json(
            'data.order_number'
        );

        $this->assertIsString($uuid);
        $this->assertMatchesRegularExpression(
            '/^LAB-\d{8}-[A-F0-9]{8}$/',
            $orderNumber
        );

        $order = $this->findLaboratoryOrder(
            $context['tenant'],
            $uuid
        );

        $this->assertNotNull($order);
        $this->assertSame(
            '15000.50',
            $order->total_amount
        );
        $this->assertCount(
            2,
            $order->items
        );

        $this->runInTenant(
            $context['tenant'],
            function () use (
                $bloodCount
            ): void {
                $bloodCount->update([
                    'name' => 'Renamed Test',
                    'category' => 'Changed',
                    'price' => 99999,
                ]);
            }
        );

        $persisted = $this
            ->findLaboratoryOrder(
                $context['tenant'],
                $uuid
            );

        $this->assertNotNull($persisted);

        $firstItem = $persisted
            ->items
            ->firstWhere(
                'lab_test_id',
                $bloodCount->id
            );

        $this->assertNotNull($firstItem);
        $this->assertSame(
            'Full Blood Count',
            $firstItem->test_name_snapshot
        );
        $this->assertSame(
            'Haematology',
            $firstItem
                ->test_category_snapshot
        );
        $this->assertSame(
            '12500.50',
            $firstItem->price_snapshot
        );
    }
}
