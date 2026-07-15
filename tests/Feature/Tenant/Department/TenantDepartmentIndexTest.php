<?php

namespace Tests\Feature\Tenant\Department;

use Tests\Support\Tenant\DepartmentManagementTestCase;

class TenantDepartmentIndexTest extends DepartmentManagementTestCase
{
    public function test_index_is_tenant_scoped_ordered_searchable_and_filterable(): void
    {
        $context = $this->authenticatedDepartmentContext(
            'department-index-a'
        );

        $otherContext = $this->authenticatedDepartmentContext(
            'department-index-b'
        );

        $this->createDepartment(
            $context['tenant'],
            [
                'name' => 'Chemistry',
                'code' => 'CHEM',
                'description' => 'Main chemistry department',
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        $this->createDepartment(
            $context['tenant'],
            [
                'name' => 'Anatomy',
                'code' => 'ANAT',
                'description' => 'Anatomy services',
                'sort_order' => 1,
                'is_active' => true,
            ]
        );

        $this->createDepartment(
            $context['tenant'],
            [
                'name' => 'Archived Chemistry',
                'code' => 'ARCH',
                'description' => 'Closed chemistry unit',
                'sort_order' => 0,
                'is_active' => false,
            ]
        );

        $this->createDepartment(
            $otherContext['tenant'],
            [
                'name' => 'Foreign Department',
                'code' => 'FOREIGN',
                'sort_order' => 0,
            ]
        );

        $response = $this
            ->withHeaders($context['headers'])
            ->getJson('/api/departments?per_page=2');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath(
                'data.0.name',
                'Archived Chemistry'
            )
            ->assertJsonPath(
                'data.1.name',
                'Anatomy'
            );

        $names = collect($response->json('data'))
            ->pluck('name')
            ->all();

        $this->assertNotContains(
            'Foreign Department',
            $names
        );

        $filtered = $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/departments'
                .'?search=chemistry'
                .'&active_only=1'
                .'&per_page=100'
            );

        $filtered
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.0.name',
                'Chemistry'
            )
            ->assertJsonPath(
                'data.0.is_active',
                true
            );
    }
}