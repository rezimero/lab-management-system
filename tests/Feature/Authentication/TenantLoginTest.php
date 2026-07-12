<?php

namespace Tests\Feature\Authentication;

use Tests\Support\Authentication\TenantAuthenticationTestCase;

class TenantLoginTest extends TenantAuthenticationTestCase
{
    public function test_tenant_user_can_login_access_context_and_logout(): void
    {
        $registration = $this->registerTestTenant();

        $loginResponse = $this->postJson('/api/login', [
            'domain' => $registration['domain'],
            'email' => $registration['email'],
            'password' => $registration['password'],
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonPath('guard', 'tenant')
            ->assertJsonPath('is_superadmin', false)
            ->assertJsonPath(
                'tenant.domain',
                $registration['domain']
            )
            ->assertJsonPath(
                'user.email',
                $registration['email']
            )
            ->assertJsonPath('user.role', 'admin');

        $token = $loginResponse->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $headers = $this->tenantHeaders(
            $token,
            $registration['domain']
        );

        $this->withHeaders($headers)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath(
                'user.email',
                $registration['email']
            )
            ->assertJsonPath(
                'tenant.domain',
                $registration['domain']
            )
            ->assertJsonPath('guard', 'tenant')
            ->assertJsonPath('is_superadmin', false);

        $this->withHeaders($headers)
            ->getJson('/api/tenant')
            ->assertOk()
            ->assertJsonPath(
                'tenant.domain',
                $registration['domain']
            )
            ->assertJsonPath('is_superadmin', false);

        $this->withHeaders($headers)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Logged out successfully.'
            );

        $this->withHeaders($headers)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_tenant_routes_require_the_tenant_domain_header(): void
    {
        $registration = $this->registerTestTenant();

        $this->withHeaders(
            $this->bearerHeaders(
                $registration['token']
            )
        )
            ->getJson('/api/departments')
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Tenant domain is required.'
            );
    }
}