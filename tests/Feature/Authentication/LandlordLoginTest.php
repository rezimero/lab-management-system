<?php

namespace Tests\Feature\Authentication;

use App\Models\Landlord\User as LandlordUser;
use Tests\Support\Authentication\LandlordAuthenticationTestCase;

class LandlordLoginTest extends LandlordAuthenticationTestCase
{
    public function test_superadmin_login_uses_landlord_guard(): void
    {
        $user = $this->createTestLandlordUser(
            role: 'superadmin',
            isActive: true
        );

        $this->assertTrue($user->isSuperAdmin());

        $response = $this->loginLandlord($user);

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Login successful (Superadmin)'
            )
            ->assertJsonPath('guard', 'landlord')
            ->assertJsonPath('is_superadmin', true)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role', 'superadmin')
            ->assertJsonPath('user.is_active', true);

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $this->assertDatabaseHas(
            'personal_access_tokens',
            [
                'tenant_id' => null,
                'tokenable_type' => (
                    new LandlordUser()
                )->getMorphClass(),
                'tokenable_id' => $user->id,
                'name' => 'auth_token',
            ],
            'landlord'
        );

        $this->withHeaders(
            $this->bearerHeaders($token)
        )
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('guard', 'landlord')
            ->assertJsonPath('is_superadmin', true)
            ->assertJsonPath('tenant', null)
            ->assertJsonPath(
                'user.email',
                $user->email
            );
    }

    public function test_inactive_superadmin_cannot_login(): void
    {
        $user = $this->createTestLandlordUser(
            role: 'superadmin',
            isActive: false
        );

        $this->assertFalse($user->isSuperAdmin());

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => static::PASSWORD,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ])
            ->assertJsonPath(
                'errors.email.0',
                'This account has been deactivated.'
            );

        $this->assertDatabaseMissing(
            'personal_access_tokens',
            [
                'tokenable_type' => (
                    new LandlordUser()
                )->getMorphClass(),
                'tokenable_id' => $user->id,
            ],
            'landlord'
        );
    }

    public function test_ordinary_landlord_user_cannot_login_to_landlord_portal(): void
    {
        $user = $this->createTestLandlordUser(
            role: 'support',
            isActive: true
        );

        $this->assertFalse($user->isSuperAdmin());

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => static::PASSWORD,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'email',
            ])
            ->assertJsonPath(
                'errors.email.0',
                'You are not authorized to access the landlord portal.'
            );
    }
}