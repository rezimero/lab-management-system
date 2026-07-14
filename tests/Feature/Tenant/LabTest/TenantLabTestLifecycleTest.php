<?php

namespace Tests\Feature\Tenant\LabTest;

use Tests\Support\Tenant\LabTestManagementTestCase;

class TenantLabTestLifecycleTest extends LabTestManagementTestCase
{
    public function test_lab_test_can_be_read_updated_and_deleted(): void
    {
        $context = $this->authenticatedLabTestContext(
            'lab-test-lifecycle-a'
        );

        $otherContext = $this->authenticatedLabTestContext(
            'lab-test-lifecycle-b'
        );

        $labTest = $this->createLabTest(
            $context['tenant'],
            [
                'name' => 'Original Test',
                'category' => 'General',
                'price' => 1000,
            ]
        );

        $this->createLabTest(
            $otherContext['tenant'],
            [
                'name' => 'Foreign Placeholder',
            ]
        );

        $foreignLabTest = $this->createLabTest(
            $otherContext['tenant'],
            [
                'name' => 'Foreign Test',
            ]
        );

        $this->withHeaders($context['headers'])
            ->getJson('/api/lab-tests/'.$labTest->id)
            ->assertOk()
            ->assertJsonPath(
                'id',
                $labTest->id
            )
            ->assertJsonPath(
                'name',
                'Original Test'
            );

        $this->withHeaders($context['headers'])
            ->getJson(
                '/api/lab-tests/'
                .$foreignLabTest->id
            )
            ->assertNotFound();

        $response = $this
            ->withHeaders($context['headers'])
            ->putJson(
                '/api/lab-tests/'.$labTest->id,
                [
                    'name' => '  Updated Test  ',
                    'description' => '   ',
                    'category' => '  Chemistry  ',
                    'price' => 2500,
                    'turnaround_time_hours' => 8,
                    'parameters' => [
                        [
                            'name' => 'Result',
                            'unit' => 'mg/dL',
                        ],
                    ],
                    'is_active' => false,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Lab test updated successfully'
            )
            ->assertJsonPath(
                'lab_test.name',
                'Updated Test'
            )
            ->assertJsonPath(
                'lab_test.description',
                null
            )
            ->assertJsonPath(
                'lab_test.category',
                'Chemistry'
            )
            ->assertJsonPath(
                'lab_test.price',
                '2500.00'
            )
            ->assertJsonPath(
                'lab_test.turnaround_time_hours',
                8
            )
            ->assertJsonPath(
                'lab_test.is_active',
                false
            );

        $updated = $this->findLabTest(
            $context['tenant'],
            $labTest->id
        );

        $this->assertNotNull($updated);
        $this->assertSame(
            'Updated Test',
            $updated->name
        );
        $this->assertFalse(
            $updated->is_active
        );

        $this->withHeaders($context['headers'])
            ->deleteJson(
                '/api/lab-tests/'.$labTest->id
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Lab test deleted successfully'
            );

        $this->assertNull(
            $this->findLabTest(
                $context['tenant'],
                $labTest->id
            )
        );
    }
}