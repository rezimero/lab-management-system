<?php

namespace Tests\Feature\Tenant\User;

use Tests\Support\Tenant\UserManagementTestCase;

class TenantUserRolesTest extends UserManagementTestCase
{
    public function test_tenant_roles_endpoint_excludes_superadmin(): void
    {
        $registration = $this->registerTestTenant(
            'user-roles'
        );

        $response = $this->withHeaders(
            $this->tenantHeaders(
                $registration['token'],
                $registration['domain']
            )
        )->getJson('/api/roles');

        $response->assertOk();

        $roles = $response->json('roles');

        $this->assertIsArray($roles);
        $this->assertArrayHasKey('admin', $roles);
        $this->assertArrayHasKey(
            'lab_technician',
            $roles
        );
        $this->assertArrayHasKey(
            'receptionist',
            $roles
        );
        $this->assertArrayHasKey('doctor', $roles);
        $this->assertArrayNotHasKey(
            'superadmin',
            $roles
        );
    }
}