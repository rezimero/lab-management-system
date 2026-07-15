<?php

namespace Tests\Feature\Tenant\User;

use Tests\Support\Tenant\UserManagementTestCase;

class TenantUserStoreTest extends UserManagementTestCase
{
    public function test_tenant_user_can_create_a_user_in_the_current_tenant(): void
    {
        $registration = $this->registerTestTenant(
            'user-store'
        );

        $response = $this->withHeaders(
            $this->tenantHeaders(
                $registration['token'],
                $registration['domain']
            )
        )->postJson('/api/users', [
            'name' => 'New Lab Technician',
            'email' => 'new-technician@example.test',
            'password' => 'Password123!',
            'role' => 'lab_technician',
            'is_active' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'User created successfully'
            )
            ->assertJsonPath(
                'user.name',
                'New Lab Technician'
            )
            ->assertJsonPath(
                'user.email',
                'new-technician@example.test'
            )
            ->assertJsonPath(
                'user.role',
                'lab_technician'
            )
            ->assertJsonPath(
                'user.is_active',
                true
            )
            ->assertJsonPath(
                'tenant.id',
                $registration['tenant']->id
            )
            ->assertJsonPath(
                'tenant.domain',
                $registration['domain']
            );

        $this->assertArrayNotHasKey(
            'password',
            $response->json('user')
        );

        $this->assertTrue(
            $this->tenantUserExists(
                $registration['tenant'],
                [
                    'email' => 'new-technician@example.test',
                    'role' => 'lab_technician',
                    'is_active' => true,
                ]
            )
        );
    }

    public function test_tenant_user_cannot_create_a_superadmin(): void
    {
        $registration = $this->registerTestTenant(
            'user-no-superadmin'
        );

        $this->withHeaders(
            $this->tenantHeaders(
                $registration['token'],
                $registration['domain']
            )
        )->postJson('/api/users', [
            'name' => 'Forbidden Superadmin',
            'email' => 'forbidden-superadmin@example.test',
            'password' => 'Password123!',
            'role' => 'superadmin',
            'is_active' => true,
        ])
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'Unauthorized to create superadmin user'
            );

        $this->assertFalse(
            $this->tenantUserExists(
                $registration['tenant'],
                [
                    'email' => (
                        'forbidden-superadmin@example.test'
                    ),
                ]
            )
        );
    }
}