<?php

namespace Tests\Feature\Tenant\Department;

use Tests\Support\Tenant\DepartmentManagementTestCase;

class TenantDepartmentShowTest extends DepartmentManagementTestCase
{
    public function test_show_uses_uuid_and_cannot_read_another_tenants_department(): void
    {
        $context = $this->authenticatedDepartmentContext(
            'department-show-a'
        );

        $otherContext = $this->authenticatedDepartmentContext(
            'department-show-b'
        );

        $department = $this->createDepartment(
            $context['tenant'],
            [
                'name' => 'Microbiology',
                'code' => 'MICRO',
                'description' => 'Microbiology unit',
            ]
        );

        $foreignDepartment = $this->createDepartment(
            $otherContext['tenant'],
            [
                'name' => 'Foreign Microbiology',
                'code' => 'FMICRO',
            ]
        );

        $this->withHeaders($context['headers'])
            ->getJson(
                '/api/departments/'.$department->uuid
            )
            ->assertOk()
            ->assertJsonPath(
                'data.uuid',
                $department->uuid
            )
            ->assertJsonPath(
                'data.name',
                'Microbiology'
            )
            ->assertJsonPath(
                'data.code',
                'MICRO'
            );

        $this->withHeaders($context['headers'])
            ->getJson(
                '/api/departments/'
                .$foreignDepartment->uuid
            )
            ->assertNotFound();
    }
}