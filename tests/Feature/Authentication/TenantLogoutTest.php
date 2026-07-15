<?php

namespace Tests\Feature\Authentication;

use Tests\Support\Authentication\TenantAuthenticationTestCase;

class TenantLogoutTest extends TenantAuthenticationTestCase
{
    public function test_logout_revokes_only_the_current_tenant_token(): void
    {
        $registration = $this->registerTestTenant();

        $secondLoginResponse = $this->postJson(
            '/api/login',
            [
                'domain' => $registration['domain'],
                'email' => $registration['email'],
                'password' => $registration['password'],
            ]
        );

        $secondLoginResponse->assertOk();

        $secondToken = $secondLoginResponse->json('token');

        $this->assertIsString($secondToken);
        $this->assertNotSame('', $secondToken);

        $firstHeaders = $this->tenantHeaders(
            $registration['token'],
            $registration['domain']
        );

        $secondHeaders = $this->tenantHeaders(
            $secondToken,
            $registration['domain']
        );

        $this->withHeaders($secondHeaders)
            ->postJson('/api/logout')
            ->assertOk();

        $this->withHeaders($secondHeaders)
            ->getJson('/api/user')
            ->assertUnauthorized();

        $this->withHeaders($firstHeaders)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath(
                'user.email',
                $registration['email']
            );
    }
}