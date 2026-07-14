<?php

namespace Tests\Feature\Tenant\LabTest;

use Tests\Support\Tenant\LabTestManagementTestCase;

class TenantLabTestIndexTest extends LabTestManagementTestCase
{
    public function test_index_is_filtered_searchable_and_tenant_scoped(): void
    {
        $context = $this->authenticatedLabTestContext(
            'lab-test-index-a'
        );

        $otherContext = $this->authenticatedLabTestContext(
            'lab-test-index-b'
        );

        $this->createLabTest(
            $context['tenant'],
            [
                'name' => 'Blood Chemistry Panel',
                'description' => 'Active chemistry service',
                'category' => 'Chemistry',
                'is_active' => true,
            ]
        );

        $this->createLabTest(
            $context['tenant'],
            [
                'name' => 'Archived Chemistry',
                'description' => 'Archived chemistry service',
                'category' => 'Chemistry',
                'is_active' => false,
            ]
        );

        $this->createLabTest(
            $context['tenant'],
            [
                'name' => 'Full Blood Count',
                'description' => 'Blood cell analysis',
                'category' => 'Haematology',
                'is_active' => true,
            ]
        );

        $this->createLabTest(
            $otherContext['tenant'],
            [
                'name' => 'Foreign Chemistry Test',
                'description' => 'Foreign chemistry service',
                'category' => 'Chemistry',
                'is_active' => true,
            ]
        );

        $response = $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/lab-tests'
                .'?category=Chemistry'
                .'&is_active=1'
                .'&search=chemistry'
                .'&per_page=100'
            );

        $response
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.name',
                'Blood Chemistry Panel'
            )
            ->assertJsonPath(
                'data.0.category',
                'Chemistry'
            )
            ->assertJsonPath(
                'data.0.is_active',
                true
            );

        $names = collect(
            $response->json('data')
        )
            ->pluck('name')
            ->all();

        $this->assertNotContains(
            'Archived Chemistry',
            $names
        );

        $this->assertNotContains(
            'Foreign Chemistry Test',
            $names
        );
    }
}