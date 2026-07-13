<?php

namespace Tests\Feature\Tenant\Department;

use Tests\Support\Tenant\DepartmentManagementTestCase;

class TenantDepartmentUpdateTest extends DepartmentManagementTestCase
{
    public function test_tenant_user_can_update_and_normalize_a_department(): void
    {
        $context = $this->authenticatedDepartmentContext(
            'department-update'
        );

        $administrator = $this->findTenantUserByEmail(
            $context['tenant'],
            $context['registration']['email']
        );

        $this->assertNotNull($administrator);

        $department = $this->createDepartment(
            $context['tenant'],
            [
                'name' => 'Old Department',
                'code' => 'OLD',
            ]
        );

        $conflict = $this->createDepartment(
            $context['tenant'],
            [
                'name' => 'Conflict Department',
                'code' => 'CONFLICT',
            ]
        );

        $response = $this
            ->withHeaders($context['headers'])
            ->putJson(
                '/api/departments/'.$department->uuid,
                [
                    'name' => '  Updated Department  ',
                    'code' => '  updated  ',
                    'description' => '   ',
                    'sort_order' => 7,
                    'is_active' => false,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Department updated successfully.'
            )
            ->assertJsonPath(
                'data.name',
                'Updated Department'
            )
            ->assertJsonPath(
                'data.code',
                'UPDATED'
            )
            ->assertJsonPath(
                'data.description',
                null
            )
            ->assertJsonPath('data.sort_order', 7)
            ->assertJsonPath('data.is_active', false);

        $updated = $this->findDepartmentByUuid(
            $context['tenant'],
            $department->uuid
        );

        $this->assertNotNull($updated);
        $this->assertSame(
            $administrator->id,
            $updated->updated_by
        );

        $this->withHeaders($context['headers'])
            ->putJson(
                '/api/departments/'.$department->uuid,
                [
                    'code' => strtolower(
                        $conflict->code
                    ),
                ]
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.code.0',
                'A department with this code already exists.'
            );
    }
}