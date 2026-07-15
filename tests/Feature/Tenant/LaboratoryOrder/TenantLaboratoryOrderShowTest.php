<?php

namespace Tests\Feature\Tenant\LaboratoryOrder;

use Tests\Support\Tenant\LaboratoryOrderManagementTestCase;

class TenantLaboratoryOrderShowTest extends LaboratoryOrderManagementTestCase
{
    public function test_show_uses_uuid_and_cannot_read_another_tenants_order(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-show-owner'
            );

        $foreignContext = $this
            ->authenticatedLaboratoryOrderContext(
                'order-show-foreign'
            );

        $patient = $this->createPatient(
            $context['tenant'],
            [
                'first_name' => 'Chidi',
                'last_name' => 'Nwosu',
            ]
        );

        $foreignPatient = $this->createPatient(
            $foreignContext['tenant']
        );

        $labTest = $this->createLabTest(
            $context['tenant'],
            [
                'name' => 'Liver Function Test',
                'category' => 'Chemistry',
                'price' => 8500,
            ]
        );

        $order = $this->createLaboratoryOrder(
            $context['tenant'],
            $patient,
            [
                'order_number' => (
                    'LAB-20260715-CHIDI001'
                ),
                'priority' => 'stat',
                'status' => 'pending',
                'referring_clinician' => (
                    'Dr. Amina Yusuf'
                ),
                'clinical_notes' => (
                    'Urgent liver assessment'
                ),
                'total_amount' => '8500.00',
                'ordered_at' => (
                    '2026-07-15 08:30:00'
                ),
            ],
            [
                [
                    'lab_test_id' => $labTest->id,
                    'test_name_snapshot' => (
                        'Liver Function Test'
                    ),
                    'test_category_snapshot' => (
                        'Chemistry'
                    ),
                    'price_snapshot' => '8500.00',
                    'turnaround_time_hours_snapshot' => 4,
                    'parameters_snapshot' => [
                        [
                            'name' => 'ALT',
                            'unit' => 'U/L',
                        ],
                    ],
                ],
            ]
        );

        $foreignOrder = $this->createLaboratoryOrder(
            $foreignContext['tenant'],
            $foreignPatient
        );

        $this->withHeaders(
            $context['headers']
        )
            ->getJson(
                '/api/laboratory-orders/'
                .$order->uuid
            )
            ->assertOk()
            ->assertJsonPath(
                'data.uuid',
                $order->uuid
            )
            ->assertJsonPath(
                'data.order_number',
                'LAB-20260715-CHIDI001'
            )
            ->assertJsonPath(
                'data.patient.full_name',
                'Chidi Nwosu'
            )
            ->assertJsonPath(
                'data.referring_clinician',
                'Dr. Amina Yusuf'
            )
            ->assertJsonPath(
                'data.priority',
                'stat'
            )
            ->assertJsonPath(
                'data.total_amount',
                '8500.00'
            )
            ->assertJsonCount(
                1,
                'data.items'
            )
            ->assertJsonPath(
                'data.items.0.test_name',
                'Liver Function Test'
            )
            ->assertJsonPath(
                'data.items.0.parameters.0.name',
                'ALT'
            );

        $this->withHeaders(
            $context['headers']
        )
            ->getJson(
                '/api/laboratory-orders/'
                .$foreignOrder->uuid
            )
            ->assertNotFound();
    }
}
