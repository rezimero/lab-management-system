<?php

namespace Tests\Feature\Tenant\LaboratoryOrder;

use Tests\Support\Tenant\LaboratoryOrderManagementTestCase;

class TenantLaboratoryOrderIndexTest extends LaboratoryOrderManagementTestCase
{
    public function test_index_is_searchable_filterable_paginated_and_tenant_scoped(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-index-owner'
            );

        $foreignContext = $this
            ->authenticatedLaboratoryOrderContext(
                'order-index-foreign'
            );

        $ada = $this->createPatient(
            $context['tenant'],
            [
                'first_name' => 'Ada',
                'last_name' => 'Okafor',
                'medical_record_number' => (
                    'MRN-ADA-001'
                ),
            ]
        );

        $bola = $this->createPatient(
            $context['tenant'],
            [
                'first_name' => 'Bola',
                'last_name' => 'Adewale',
                'medical_record_number' => (
                    'MRN-BOLA-001'
                ),
            ]
        );

        $foreignPatient = $this->createPatient(
            $foreignContext['tenant'],
            [
                'first_name' => 'Foreign',
                'last_name' => 'Patient',
            ]
        );

        $labTest = $this->createLabTest(
            $context['tenant'],
            [
                'name' => 'Full Blood Count',
                'category' => 'Haematology',
                'price' => 12500.50,
            ]
        );

        $adaOrder = $this->createLaboratoryOrder(
            $context['tenant'],
            $ada,
            [
                'order_number' => (
                    'LAB-20260710-ADA00001'
                ),
                'priority' => 'urgent',
                'status' => 'pending',
                'total_amount' => '12500.50',
                'ordered_at' => (
                    '2026-07-10 09:00:00'
                ),
            ],
            [
                [
                    'lab_test_id' => $labTest->id,
                    'test_name_snapshot' => (
                        'Full Blood Count'
                    ),
                    'test_category_snapshot' => (
                        'Haematology'
                    ),
                    'price_snapshot' => '12500.50',
                ],
            ]
        );

        $bolaOrder = $this->createLaboratoryOrder(
            $context['tenant'],
            $bola,
            [
                'order_number' => (
                    'LAB-20260711-BOLA0001'
                ),
                'priority' => 'routine',
                'status' => 'completed',
                'total_amount' => '2500.00',
                'ordered_at' => (
                    '2026-07-11 09:00:00'
                ),
            ]
        );

        $this->createLaboratoryOrder(
            $foreignContext['tenant'],
            $foreignPatient,
            [
                'order_number' => (
                    'LAB-20260712-FOREIGN1'
                ),
                'priority' => 'urgent',
                'status' => 'pending',
                'ordered_at' => (
                    '2026-07-12 09:00:00'
                ),
            ]
        );

        $response = $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-orders'
                .'?search=Ada%20Okafor'
                .'&status=pending'
                .'&priority=urgent'
                .'&patient_id='.$ada->id
                .'&date_from=2026-07-01'
                .'&date_to=2026-07-31'
                .'&per_page=10'
            );

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'meta.total',
                1
            )
            ->assertJsonPath(
                'data.0.uuid',
                $adaOrder->uuid
            )
            ->assertJsonPath(
                'data.0.order_number',
                'LAB-20260710-ADA00001'
            )
            ->assertJsonPath(
                'data.0.patient.full_name',
                'Ada Okafor'
            )
            ->assertJsonPath(
                'data.0.priority',
                'urgent'
            )
            ->assertJsonPath(
                'data.0.status',
                'pending'
            )
            ->assertJsonCount(
                1,
                'data.0.items'
            );

        $this->withHeaders(
            $context['headers']
        )
            ->getJson(
                '/api/laboratory-orders'
                .'?search=MRN-ADA-001'
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.uuid',
                $adaOrder->uuid
            );

        $this->withHeaders(
            $context['headers']
        )
            ->getJson(
                '/api/laboratory-orders'
                .'?per_page=1'
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'meta.total',
                2
            )
            ->assertJsonPath(
                'data.0.uuid',
                $bolaOrder->uuid
            );
    }
}
