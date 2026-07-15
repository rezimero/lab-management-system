<?php

namespace Tests\Feature\Tenant\Department;

use Tests\Support\Tenant\DepartmentManagementTestCase;

class TenantDepartmentLifecycleTest extends DepartmentManagementTestCase
{
    public function test_department_can_be_activated_deactivated_and_soft_deleted(): void
    {
        $context = $this->authenticatedDepartmentContext(
            'department-lifecycle'
        );

        $department = $this->createDepartment(
            $context['tenant'],
            [
                'name' => 'Lifecycle Department',
                'code' => 'LIFE',
                'is_active' => false,
            ]
        );

        $this->withHeaders($context['headers'])
            ->patchJson(
                '/api/departments/'
                .$department->uuid
                .'/activate'
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Department activated successfully.'
            )
            ->assertJsonPath(
                'data.is_active',
                true
            );

        $activated = $this->findDepartmentByUuid(
            $context['tenant'],
            $department->uuid
        );

        $this->assertNotNull($activated);
        $this->assertTrue($activated->is_active);

        $this->withHeaders($context['headers'])
            ->patchJson(
                '/api/departments/'
                .$department->uuid
                .'/deactivate'
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Department deactivated successfully.'
            )
            ->assertJsonPath(
                'data.is_active',
                false
            );

        $deactivated = $this->findDepartmentByUuid(
            $context['tenant'],
            $department->uuid
        );

        $this->assertNotNull($deactivated);
        $this->assertFalse($deactivated->is_active);

        $this->withHeaders($context['headers'])
            ->deleteJson(
                '/api/departments/'.$department->uuid
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Department deleted successfully.'
            );

        $this->assertNull(
            $this->findDepartmentByUuid(
                $context['tenant'],
                $department->uuid
            )
        );

        $deleted = $this->findDepartmentByUuid(
            $context['tenant'],
            $department->uuid,
            true
        );

        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted->deleted_at);

        $this->withHeaders($context['headers'])
            ->getJson(
                '/api/departments/'.$department->uuid
            )
            ->assertNotFound();
    }
}