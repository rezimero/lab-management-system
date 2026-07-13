<?php

namespace Tests\Feature\Tenant\User;

use Tests\Support\Tenant\UserManagementTestCase;

class TenantUserUpdateTest extends UserManagementTestCase
{
    public function test_tenant_user_can_update_a_user_in_the_current_tenant(): void
    {
        $registration = $this->registerTestTenant(
            'user-update'
        );

        $target = $this->createTenantUser(
            $registration['tenant'],
            [
                'name' => 'Original User Name',
                'email' => 'original-user@example.test',
                'role' => 'receptionist',
            ]
        );

        $response = $this->withHeaders(
            $this->tenantHeaders(
                $registration['token'],
                $registration['domain']
            )
        )->putJson('/api/users/'.$target->id, [
            'name' => 'Updated User Name',
            'email' => 'updated-user@example.test',
            'is_active' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'User updated successfully'
            )
            ->assertJsonPath(
                'user.name',
                'Updated User Name'
            )
            ->assertJsonPath(
                'user.email',
                'updated-user@example.test'
            )
            ->assertJsonPath(
                'user.is_active',
                false
            );

        $this->assertTrue(
            $this->tenantUserExists(
                $registration['tenant'],
                [
                    'id' => $target->id,
                    'name' => 'Updated User Name',
                    'email' => 'updated-user@example.test',
                    'is_active' => false,
                ]
            )
        );
    }

    public function test_role_assignment_updates_role_but_rejects_superadmin(): void
    {
        $registration = $this->registerTestTenant(
            'user-role'
        );

        $target = $this->createTenantUser(
            $registration['tenant'],
            [
                'email' => 'role-target@example.test',
                'role' => 'receptionist',
            ]
        );

        $headers = $this->tenantHeaders(
            $registration['token'],
            $registration['domain']
        );

        $this->withHeaders($headers)
            ->postJson(
                '/api/users/'.$target->id.'/assign-role',
                [
                    'role' => 'doctor',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Role assigned successfully'
            )
            ->assertJsonPath(
                'user.role',
                'doctor'
            );

        $this->assertTrue(
            $this->tenantUserExists(
                $registration['tenant'],
                [
                    'id' => $target->id,
                    'role' => 'doctor',
                ]
            )
        );

        $this->withHeaders($headers)
            ->postJson(
                '/api/users/'.$target->id.'/assign-role',
                [
                    'role' => 'superadmin',
                ]
            )
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'Unauthorized'
            );

        $freshTarget = $this->findTenantUserByEmail(
            $registration['tenant'],
            'role-target@example.test'
        );

        $this->assertNotNull($freshTarget);
        $this->assertSame(
            'doctor',
            $freshTarget->role
        );
    }
}