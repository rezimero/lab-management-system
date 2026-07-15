<?php

namespace Tests\Feature\Authentication;

use App\Services\Authentication\AuthenticationService;
use Tests\Support\Authentication\LandlordAuthenticationTestCase;

class SuperAdminAuthorizationTest extends LandlordAuthenticationTestCase
{
    public function test_superadmin_can_access_landlord_dashboard(): void
    {
        $user = $this->createTestLandlordUser(
            role: 'superadmin',
            isActive: true
        );

        $token = $this->loginLandlord($user)
            ->assertOk()
            ->json('token');

        $this->assertIsString($token);

        $this->withHeaders(
            $this->bearerHeaders($token)
        )
            ->getJson('/api/landlord/dashboard/stats')
            ->assertOk()
            ->assertJsonStructure([
                'total_tenants',
                'total_users',
                'total_patients',
                'total_lab_tests',
                'total_test_results',
                'tenants',
            ]);

        $this->assertFalse(
            app()->bound('superadmin.override')
        );
    }

    public function test_stale_session_cannot_grant_god_mode_to_ordinary_landlord_user(): void
    {
        $user = $this->createTestLandlordUser(
            role: 'support',
            isActive: true
        );

        $token = app(AuthenticationService::class)
            ->createToken($user);

        $this->withSession([
            'is_superadmin' => true,
            'landlord_user_id' => $user->id,
            'landlord_user_email' => $user->email,
        ])
            ->withHeaders(
                $this->bearerHeaders($token)
            )
            ->getJson('/api/landlord/dashboard/stats')
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'Access denied. Landlord superadmin privileges are required.'
            );

        $this->assertFalse(
            app()->bound('superadmin.override')
        );
    }
}