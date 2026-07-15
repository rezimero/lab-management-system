<?php

namespace Tests\Feature\Tenant\LaboratoryOrder;

use Tests\Support\Tenant\LaboratoryOrderManagementTestCase;

class TenantLaboratoryOrderValidationTest extends LaboratoryOrderManagementTestCase
{
    public function test_order_requires_a_patient_and_at_least_one_test(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-required'
            );

        $this->withHeaders(
            $context['headers']
        )
            ->postJson(
                '/api/laboratory-orders',
                []
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'patient_id',
                'items',
            ]);
    }

    public function test_order_rejects_an_inactive_patient(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-inactive-patient'
            );

        $patient = $this->createPatient(
            $context['tenant'],
            [
                'is_active' => false,
            ]
        );

        $labTest = $this->createLabTest(
            $context['tenant']
        );

        $this->withHeaders(
            $context['headers']
        )
            ->postJson(
                '/api/laboratory-orders',
                [
                    'patient_id' => $patient->id,
                    'items' => [
                        [
                            'lab_test_id' => $labTest->id,
                        ],
                    ],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.patient_id.0',
                'The selected patient is unavailable or inactive.'
            );

        $this->assertSame(
            0,
            $this->tenantTableCount(
                $context['tenant'],
                'laboratory_orders'
            )
        );
    }

    public function test_order_rejects_an_inactive_lab_test(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-inactive-test'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $labTest = $this->createLabTest(
            $context['tenant'],
            [
                'is_active' => false,
            ]
        );

        $response = $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/laboratory-orders',
                [
                    'patient_id' => $patient->id,
                    'items' => [
                        [
                            'lab_test_id' => $labTest->id,
                        ],
                    ],
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.lab_test_id',
            ]);

        $errors = $response->json('errors');

        $this->assertIsArray($errors);
        $this->assertArrayHasKey(
            'items.0.lab_test_id',
            $errors
        );
        $this->assertSame(
            'The selected laboratory test is unavailable or inactive.',
            $errors['items.0.lab_test_id'][0]
        );

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

    public function test_order_rejects_duplicate_lab_tests(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-duplicate-test'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $labTest = $this->createLabTest(
            $context['tenant']
        );

        $this->withHeaders(
            $context['headers']
        )
            ->postJson(
                '/api/laboratory-orders',
                [
                    'patient_id' => $patient->id,
                    'items' => [
                        [
                            'lab_test_id' => $labTest->id,
                        ],
                        [
                            'lab_test_id' => $labTest->id,
                        ],
                    ],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.lab_test_id',
                'items.1.lab_test_id',
            ]);

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
