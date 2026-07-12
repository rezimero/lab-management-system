<?php

namespace Tests\Feature\Authentication;

use Tests\Support\Authentication\LandlordAuthenticationTestCase;

class LandlordTokenIsolationTest extends LandlordAuthenticationTestCase
{
    public function test_tenant_token_cannot_access_landlord_routes(): void
    {
        $registration = $this->registerLandlordTestTenant();

        $this->withHeaders(
            $this->bearerHeaders(
                $registration['token']
            )
        )
            ->getJson('/api/landlord/dashboard/stats')
            ->assertUnauthorized();
    }

    public function test_superadmin_token_cannot_authenticate_tenant_routes(): void
    {
        $registration = $this->registerLandlordTestTenant();

        $superadmin = $this->createTestLandlordUser(
            role: 'superadmin',
            isActive: true
        );

        $superadminToken = $this->loginLandlord(
            $superadmin
        )
            ->assertOk()
            ->json('token');

        $this->assertIsString($superadminToken);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => (
                'Bearer '.$superadminToken
            ),
            'X-Tenant-Domain' => (
                $registration['domain']
            ),
        ])
            ->getJson('/api/departments')
            ->assertUnauthorized();
    }
}