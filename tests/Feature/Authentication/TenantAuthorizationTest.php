<?php

namespace Tests\Feature\Authentication;

use Tests\Support\Authentication\TenantAuthenticationTestCase;

class TenantAuthorizationTest extends TenantAuthenticationTestCase
{
    public function test_tenant_user_cannot_access_landlord_routes(): void
    {
        $registration = $this->registerTestTenant();

        $this->withHeaders(
            $this->bearerHeaders(
                $registration['token']
            )
        )
            ->getJson('/api/landlord/dashboard/stats')
            ->assertUnauthorized();
    }
}