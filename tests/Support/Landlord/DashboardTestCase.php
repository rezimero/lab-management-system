<?php

namespace Tests\Support\Landlord;

use Tests\Support\Authentication\LandlordAuthenticationTestCase;

abstract class DashboardTestCase extends LandlordAuthenticationTestCase
{
    /**
     * Create and authenticate an active superadmin.
     *
     * @return array<string, string>
     */
    protected function superadminHeaders(): array
    {
        $superadmin = $this->createTestLandlordUser(
            role: 'superadmin',
            isActive: true
        );

        $response = $this->loginLandlord($superadmin);

        $response
            ->assertOk()
            ->assertJsonPath('guard', 'landlord')
            ->assertJsonPath('is_superadmin', true);

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        return $this->bearerHeaders($token);
    }
}