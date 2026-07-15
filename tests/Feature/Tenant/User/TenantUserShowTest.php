<?php

namespace Tests\Feature\Tenant\User;

use Tests\Support\Tenant\UserManagementTestCase;

class TenantUserShowTest extends UserManagementTestCase
{
    public function test_tenant_user_can_view_a_user_in_the_same_tenant(): void
    {
        $registration = $this->registerTestTenant('user-show');

        $target = $this->createTenantUser(
            $registration['tenant'],
            [
                'name' => 'Visible Tenant User',
                'email' => 'visible-user@example.test',
                'role' => 'doctor',
            ]
        );

        $response = $this->withHeaders(
            $this->tenantHeaders(
                $registration['token'],
                $registration['domain']
            )
        )->getJson('/api/users/'.$target->id);

        $response
            ->assertOk()
            ->assertJsonPath('id', $target->id)
            ->assertJsonPath(
                'name',
                'Visible Tenant User'
            )
            ->assertJsonPath(
                'email',
                'visible-user@example.test'
            )
            ->assertJsonPath('role', 'doctor')
            ->assertJsonPath('is_active', true);

        $this->assertArrayNotHasKey(
            'password',
            $response->json()
        );
    }

    public function test_show_returns_not_found_for_a_missing_tenant_user(): void
    {
        $registration = $this->registerTestTenant(
            'user-missing'
        );

        $this->withHeaders(
            $this->tenantHeaders(
                $registration['token'],
                $registration['domain']
            )
        )
            ->getJson('/api/users/999999')
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'User not found'
            );
    }
}