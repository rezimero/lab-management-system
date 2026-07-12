<?php

namespace Tests\Feature\Authentication;

use Tests\Support\Authentication\LandlordAuthenticationTestCase;

class LandlordLogoutTest extends LandlordAuthenticationTestCase
{
    public function test_logout_revokes_only_the_current_superadmin_token(): void
    {
        $user = $this->createTestLandlordUser(
            role: 'superadmin',
            isActive: true
        );

        $firstToken = $this->loginLandlord($user)
            ->assertOk()
            ->json('token');

        $secondToken = $this->loginLandlord($user)
            ->assertOk()
            ->json('token');

        $this->assertIsString($firstToken);
        $this->assertIsString($secondToken);
        $this->assertNotSame(
            $firstToken,
            $secondToken
        );

        $firstHeaders = $this->bearerHeaders(
            $firstToken
        );

        $secondHeaders = $this->bearerHeaders(
            $secondToken
        );

        $this->withHeaders($secondHeaders)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Logged out successfully.'
            );

        $this->withHeaders($secondHeaders)
            ->getJson('/api/user')
            ->assertUnauthorized();

        $this->withHeaders($firstHeaders)
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath(
                'user.email',
                $user->email
            )
            ->assertJsonPath(
                'is_superadmin',
                true
            );
    }
}