<?php

namespace Tests\Feature\Tenant\Department;

use Tests\Support\Tenant\DepartmentManagementTestCase;

class TenantDepartmentStoreTest extends DepartmentManagementTestCase
{
    public function test_tenant_user_can_create_a_normalized_department(): void
    {
        $context = $this->authenticatedDepartmentContext(
            'department-store'
        );

        $administrator = $this->findTenantUserByEmail(
            $context['tenant'],
            $context['registration']['email']
        );

        $this->assertNotNull($administrator);

        $response = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/departments', [
                'name' => '  Clinical Chemistry  ',
                'code' => '  chem  ',
                'description' => '  Routine services  ',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Department created successfully.'
            )
            ->assertJsonPath(
                'data.name',
                'Clinical Chemistry'
            )
            ->assertJsonPath('data.code', 'CHEM')
            ->assertJsonPath(
                'data.description',
                'Routine services'
            )
            ->assertJsonPath('data.sort_order', 0)
            ->assertJsonPath('data.is_active', true);

        $uuid = $response->json('data.uuid');

        $this->assertIsString($uuid);
        $this->assertNotSame('', $uuid);

        $department = $this->findDepartmentByUuid(
            $context['tenant'],
            $uuid
        );

        $this->assertNotNull($department);
        $this->assertSame(
            'Clinical Chemistry',
            $department->name
        );
        $this->assertSame('CHEM', $department->code);
        $this->assertSame(
            $administrator->id,
            $department->created_by
        );
        $this->assertSame(
            $administrator->id,
            $department->updated_by
        );
    }

    public function test_store_rejects_a_duplicate_normalized_code(): void
    {
        $context = $this->authenticatedDepartmentContext(
            'department-duplicate'
        );

        $this->createDepartment(
            $context['tenant'],
            [
                'name' => 'Existing Department',
                'code' => 'DUP',
            ]
        );

        $this->withHeaders($context['headers'])
            ->postJson('/api/departments', [
                'name' => 'New Department',
                'code' => '  dup  ',
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.code.0',
                'A department with this code already exists.'
            );
    }
}