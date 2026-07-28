<?php

namespace Tests\Support\Tenant;

use App\Models\Tenant;
use App\Models\TenantProfile;

abstract class ProfileManagementTestCase extends
    UserManagementTestCase
{
    /**
     * Authenticate a tenant user with a specified role.
     *
     * @return array<string, string>
     */
    protected function headersForProfileRole(
        Tenant $tenant,
        string $domain,
        string $role
    ): array {
        $password = 'Password123!';

        $user = $this->createTenantUser(
            $tenant,
            [
                'role' => $role,
                'password' => $password,
            ]
        );

        $this->resetProfileAuthentication();

        $response = $this->postJson(
            '/api/login',
            [
                'domain' => $domain,
                'email' => $user->email,
                'password' => $password,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'guard',
                'tenant'
            )
            ->assertJsonPath(
                'user.role',
                $role
            );

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $this->resetProfileAuthentication();

        return $this->tenantHeaders(
            $token,
            $domain
        );
    }

    /**
     * Retrieve the landlord profile belonging to a tenant.
     */
    protected function storedTenantProfile(
        Tenant $tenant
    ): TenantProfile {
        return TenantProfile::query()
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();
    }

    /**
     * Simulate a fresh HTTP authentication lifecycle.
     */
    protected function resetProfileAuthentication(): void
    {
        $this->restoreLandlordContext();
        $this->clearAuthenticationState();
    }
}