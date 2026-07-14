<?php

namespace Tests\Feature\Tenant\LaboratoryOrder;

use Tests\Support\Tenant\LaboratoryOrderManagementTestCase;

class TenantLaboratoryOrderIsolationTest extends LaboratoryOrderManagementTestCase
{
    public function test_order_cannot_use_another_tenants_patient(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-patient-owner'
            );

        $foreignContext = $this
            ->authenticatedLaboratoryOrderContext(
                'order-patient-foreign'
            );

        $localTest = $this->createLabTest(
            $context['tenant']
        );

        $foreignPatient = $this->createPatient(
            $foreignContext['tenant']
        );

        $this->withHeaders(
            $context['headers']
        )
            ->postJson(
                '/api/laboratory-orders',
                [
                    'patient_id' => (
                        $foreignPatient->id
                    ),
                    'items' => [
                        [
                            'lab_test_id' => (
                                $localTest->id
                            ),
                        ],
                    ],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'patient_id',
            ]);

        $this->assertSame(
            0,
            $this->tenantTableCount(
                $context['tenant'],
                'laboratory_orders'
            )
        );
    }

    public function test_order_cannot_use_another_tenants_lab_test(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-test-owner'
            );

        $foreignContext = $this
            ->authenticatedLaboratoryOrderContext(
                'order-test-foreign'
            );

        $localPatient = $this->createPatient(
            $context['tenant']
        );

        $foreignTest = $this->createLabTest(
            $foreignContext['tenant']
        );

        $this->withHeaders(
            $context['headers']
        )
            ->postJson(
                '/api/laboratory-orders',
                [
                    'patient_id' => (
                        $localPatient->id
                    ),
                    'items' => [
                        [
                            'lab_test_id' => (
                                $foreignTest->id
                            ),
                        ],
                    ],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'items.0.lab_test_id',
            ]);

        $this->assertSame(
            0,
            $this->tenantTableCount(
                $context['tenant'],
                'laboratory_orders'
            )
        );
    }
}
