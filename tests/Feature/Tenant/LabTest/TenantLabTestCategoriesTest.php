<?php

namespace Tests\Feature\Tenant\LabTest;

use Tests\Support\Tenant\LabTestManagementTestCase;

class TenantLabTestCategoriesTest extends LabTestManagementTestCase
{
    public function test_categories_are_distinct_sorted_and_tenant_scoped(): void
    {
        $context = $this->authenticatedLabTestContext(
            'lab-test-categories-a'
        );

        $otherContext = $this->authenticatedLabTestContext(
            'lab-test-categories-b'
        );

        $this->createLabTest(
            $context['tenant'],
            [
                'category' => 'Haematology',
            ]
        );

        $this->createLabTest(
            $context['tenant'],
            [
                'category' => 'Chemistry',
            ]
        );

        $this->createLabTest(
            $context['tenant'],
            [
                'category' => 'Chemistry',
            ]
        );

        $this->createLabTest(
            $otherContext['tenant'],
            [
                'category' => 'Foreign Category',
            ]
        );

        $this->withHeaders($context['headers'])
            ->getJson('/api/lab-tests/categories')
            ->assertOk()
            ->assertExactJson([
                'categories' => [
                    'Chemistry',
                    'Haematology',
                ],
            ]);
    }
}