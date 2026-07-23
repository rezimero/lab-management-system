<?php

namespace Tests\Feature\Landlord;

use Tests\Support\Landlord\LandlordTenantUserTestCase;

class LandlordTenantUserMutationContractTest extends
    LandlordTenantUserTestCase
{
    public function test_role_assignment_revokes_existing_tenant_sessions(): void
    {
        $registration =
            $this->registerLandlordTestTenant();

        $tenant = $registration['tenant'];
        $headers = $this->superadminHeaders();
        $password = 'SessionUser123!';

        $createResponse = $this->createTenantUser(
            headers: $headers,
            tenantId: (int) $tenant->id,
            attributes: [
                'name' => 'Session User',
                'email' =>
                    'session-user@example.test',
                'password' => $password,
                'password_confirmation' =>
                    $password,
                'role' => 'doctor',
            ]
        );

        $createResponse->assertCreated();

        $userId = $createResponse->json('user.id');

        $this->assertIsInt($userId);

        $tenantToken = $this->loginTenantUser(
            domain: $registration['domain'],
            email: 'session-user@example.test',
            password: $password
        );

        $this
            ->withHeaders($headers)
            ->postJson(
                '/api/landlord/users/'
                .$userId
                .'/assign-role',
                [
                    'tenant_id' => $tenant->id,
                    'role' => 'receptionist',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Role assigned successfully.'
            )
            ->assertJsonPath(
                'user.role',
                'receptionist'
            )
            ->assertJsonPath(
                'sessions_revoked',
                true
            );

        $this
            ->withHeaders(
                $this->tenantHeaders(
                    $tenantToken,
                    $registration['domain']
                )
            )
            ->getJson('/api/users/'.$userId)
            ->assertUnauthorized();
    }

    public function test_superadmin_can_delete_a_non_admin_tenant_user(): void
    {
        $registration =
            $this->registerLandlordTestTenant();

        $tenant = $registration['tenant'];
        $headers = $this->superadminHeaders();

        $createResponse = $this->createTenantUser(
            headers: $headers,
            tenantId: (int) $tenant->id,
            attributes: [
                'name' => 'Delete User',
                'email' =>
                    'delete-user@example.test',
                'role' => 'doctor',
            ]
        );

        $createResponse->assertCreated();

        $userId = $createResponse->json('user.id');

        $this->assertIsInt($userId);

        $this
            ->withHeaders($headers)
            ->deleteJson(
                '/api/landlord/users/'
                .$userId,
                [
                    'tenant_id' => $tenant->id,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'User deleted successfully.'
            )
            ->assertJsonPath(
                'user.id',
                $userId
            )
            ->assertJsonPath(
                'user.tenant_id',
                $tenant->id
            );

        $this
            ->withHeaders($headers)
            ->getJson(
                '/api/landlord/users/'
                .$userId
                .'?tenant_id='
                .$tenant->id
            )
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'User not found in the selected tenant.'
            );
    }
}