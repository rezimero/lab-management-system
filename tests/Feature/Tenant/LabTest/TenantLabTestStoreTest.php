<?php

namespace Tests\Feature\Tenant\LabTest;

use Tests\Support\Tenant\LabTestManagementTestCase;

class TenantLabTestStoreTest extends LabTestManagementTestCase
{
    public function test_tenant_user_can_create_a_complete_lab_test(): void
    {
        $context = $this->authenticatedLabTestContext(
            'lab-test-store'
        );

        $response = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/lab-tests', [
                'name' => '  Full Blood Count  ',
                'description' => '  Complete blood analysis  ',
                'category' => '  Haematology  ',
                'price' => 12500.50,
                'turnaround_time_hours' => 4,
                'parameters' => [
                    [
                        'name' => 'Haemoglobin',
                        'unit' => 'g/dL',
                    ],
                    [
                        'name' => 'White Blood Cells',
                        'unit' => '10^9/L',
                    ],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Lab test created successfully'
            )
            ->assertJsonPath(
                'lab_test.name',
                'Full Blood Count'
            )
            ->assertJsonPath(
                'lab_test.category',
                'Haematology'
            )
            ->assertJsonPath(
                'lab_test.description',
                'Complete blood analysis'
            )
            ->assertJsonPath(
                'lab_test.price',
                '12500.50'
            )
            ->assertJsonPath(
                'lab_test.turnaround_time_hours',
                4
            )
            ->assertJsonPath(
                'lab_test.is_active',
                true
            )
            ->assertJsonCount(
                2,
                'lab_test.parameters'
            );

        $id = $response->json('lab_test.id');

        $this->assertIsInt($id);

        $labTest = $this->findLabTest(
            $context['tenant'],
            $id
        );

        $this->assertNotNull($labTest);
        $this->assertSame(
            'Full Blood Count',
            $labTest->name
        );
        $this->assertSame(
            'Haematology',
            $labTest->category
        );
        $this->assertSame(
            '12500.50',
            $labTest->price
        );
        $this->assertCount(
            2,
            $labTest->parameters
        );
    }

    public function test_store_requires_name_and_category(): void
    {
        $context = $this->authenticatedLabTestContext(
            'lab-test-validation'
        );

        $this->withHeaders($context['headers'])
            ->postJson('/api/lab-tests', [
                'price' => 5000,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'category',
            ]);
    }
}