<?php

namespace Tests\Feature\Tenant\User;

use Tests\Support\Tenant\UserManagementTestCase;

class TenantUserDeleteTest extends UserManagementTestCase
{
    public function test_user_can_delete_another_user_but_not_their_own_account(): void
    {
        $registration = $this->registerTestTenant(
            'user-delete'
        );

        $target = $this->createTenantUser(
            $registration['tenant'],
            [
                'email' => 'delete-target@example.test',
            ]
        );

        $admin = $this->findTenantUserByEmail(
            $registration['tenant'],
            $registration['email']
        );

        $this->assertNotNull($admin);

        $headers = $this->tenantHeaders(
            $registration['token'],
            $registration['domain']
        );

        $this->withHeaders($headers)
            ->deleteJson('/api/users/'.$target->id)
            ->assertOk()
            ->assertJsonPath(
                'message',
                'User deleted successfully'
            );

        $this->assertFalse(
            $this->tenantUserExists(
                $registration['tenant'],
                [
                    'id' => $target->id,
                ]
            )
        );

        $this->withHeaders($headers)
            ->deleteJson('/api/users/'.$admin->id)
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Cannot delete your own account'
            );

        $this->assertTrue(
            $this->tenantUserExists(
                $registration['tenant'],
                [
                    'id' => $admin->id,
                    'email' => $registration['email'],
                ]
            )
        );
    }
}