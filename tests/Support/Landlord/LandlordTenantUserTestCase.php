<?php

namespace Tests\Support\Landlord;

use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Support\Authentication\LandlordAuthenticationTestCase;

abstract class LandlordTenantUserTestCase extends
    LandlordAuthenticationTestCase
{
    /**
     * @return array<string, string>
     */
    protected function superadminHeaders(): array
    {
        $superadmin = $this->createTestLandlordUser(
            role: 'superadmin',
            isActive: true
        );

        $login = $this->loginLandlord($superadmin);

        $login
            ->assertOk()
            ->assertJsonPath('guard', 'landlord')
            ->assertJsonPath('is_superadmin', true);

        $token = $login->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        return $this->bearerHeaders($token);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function createTenantUser(
        array $headers,
        int $tenantId,
        array $attributes = []
    ): TestResponse {
        $password = $attributes['password']
            ?? 'CreatedUser123!';

        $payload = array_merge(
            [
                'tenant_id' => $tenantId,
                'name' => 'Created Tenant User',
                'email' => sprintf(
                    'landlord-user-%s@example.test',
                    strtolower(Str::random(12))
                ),
                'password' => $password,
                'password_confirmation' => $password,
                'role' => 'doctor',
                'is_active' => true,
                'email_verified' => false,
            ],
            $attributes
        );

        $payload['password_confirmation'] =
            $attributes['password_confirmation']
            ?? $password;

        return $this
            ->withHeaders($headers)
            ->postJson(
                '/api/landlord/users',
                $payload
            );
    }

    protected function loginTenantUser(
        string $domain,
        string $email,
        string $password
    ): string {
        $response = $this->postJson(
            '/api/login',
            [
                'domain' => $domain,
                'email' => $email,
                'password' => $password,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('guard', 'tenant')
            ->assertJsonPath('is_superadmin', false);

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $this->restoreLandlordContext();

        return $token;
    }
}