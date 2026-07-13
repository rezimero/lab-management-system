<?php

namespace Tests\Feature\Tenant\User;

use Tests\Support\Tenant\UserManagementTestCase;

class TenantUserIndexTest extends UserManagementTestCase
{
    public function test_index_lists_only_users_from_the_authenticated_tenant(): void
    {
        $tenantA = $this->registerTestTenant('users-a');

        $userA = $this->createTenantUser(
            $tenantA['tenant'],
            [
                'name' => 'Tenant A Receptionist',
                'email' => 'tenant-a-receptionist@example.test',
            ]
        );

        $tenantB = $this->registerTestTenant('users-b');

        $userB = $this->createTenantUser(
            $tenantB['tenant'],
            [
                'name' => 'Tenant B Receptionist',
                'email' => 'tenant-b-receptionist@example.test',
            ]
        );

        /*
         * Logging in again ensures session metadata belongs to tenant A,
         * rather than the most recently registered tenant.
         */
        $token = $this->loginRegisteredTenant($tenantA);

        $response = $this->withHeaders(
            $this->tenantHeaders(
                $token,
                $tenantA['domain']
            )
        )->getJson('/api/users?per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('users.total', 2)
            ->assertJsonPath('users.per_page', 10)
            ->assertJsonPath('users.current_page', 1)
            ->assertJsonPath('users.last_page', 1)
            ->assertJsonPath('current_user_role', 'admin')
            ->assertJsonPath('is_superadmin', false)
            ->assertJsonPath(
                'tenant_name',
                $tenantA['tenant']->name
            )
            ->assertJsonPath(
                'user_email',
                $tenantA['email']
            );

        $emails = collect(
            $response->json('users.data')
        )->pluck('email')->all();

        $this->assertContains(
            $tenantA['email'],
            $emails
        );

        $this->assertContains(
            $userA->email,
            $emails
        );

        $this->assertNotContains(
            $userB->email,
            $emails
        );

        $roles = $response->json('available_roles');

        $this->assertIsArray($roles);
        $this->assertArrayHasKey('admin', $roles);
        $this->assertArrayHasKey('doctor', $roles);
        $this->assertArrayNotHasKey(
            'superadmin',
            $roles
        );
    }
}