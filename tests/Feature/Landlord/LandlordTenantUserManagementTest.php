<?php

namespace Tests\Feature\Landlord;

use Tests\Support\Authentication\LandlordAuthenticationTestCase;

class LandlordTenantUserManagementTest extends
    LandlordAuthenticationTestCase
{
    public function test_superadmin_can_create_and_fully_update_a_tenant_user(): void
    {
        $tenantRegistration =
            $this->registerLandlordTestTenant();

        $tenant =
            $tenantRegistration['tenant'];

        $superadmin =
            $this->createTestLandlordUser(
                role: 'superadmin',
                isActive: true
            );

        $login =
            $this->loginLandlord(
                $superadmin
            );

        $login->assertOk();

        $token =
            $login->json('token');

        $this->assertIsString($token);

        $headers = [
            'Authorization' =>
                'Bearer '.$token,
            'Accept' =>
                'application/json',
        ];

        $password =
            'TemporaryUser123!';

        $createResponse =
            $this
                ->withHeaders($headers)
                ->postJson(
                    '/api/landlord/users',
                    [
                        'tenant_id' =>
                            $tenant->id,
                        'name' =>
                            'Tenant Doctor',
                        'email' =>
                            'tenant-doctor@example.test',
                        'password' =>
                            $password,
                        'password_confirmation' =>
                            $password,
                        'role' =>
                            'doctor',
                        'is_active' =>
                            true,
                        'email_verified' =>
                            false,
                    ]
                );

        $createResponse
            ->assertCreated()
            ->assertJsonPath(
                'user.tenant_id',
                $tenant->id
            )
            ->assertJsonPath(
                'user.name',
                'Tenant Doctor'
            )
            ->assertJsonPath(
                'user.role',
                'doctor'
            )
            ->assertJsonPath(
                'user.is_active',
                true
            )
            ->assertJsonPath(
                'user.email_verified',
                false
            );

        $userId =
            $createResponse
                ->json('user.id');

        $this->assertIsInt($userId);

        $updateResponse =
            $this
                ->withHeaders($headers)
                ->putJson(
                    '/api/landlord/users/'
                    .$userId,
                    [
                        'tenant_id' =>
                            $tenant->id,
                        'name' =>
                            'Senior Tenant Doctor',
                        'email' =>
                            'senior-doctor@example.test',
                        'role' =>
                            'admin',
                        'is_active' =>
                            true,
                        'email_verified' =>
                            true,
                    ]
                );

        $updateResponse
            ->assertOk()
            ->assertJsonPath(
                'user.name',
                'Senior Tenant Doctor'
            )
            ->assertJsonPath(
                'user.email',
                'senior-doctor@example.test'
            )
            ->assertJsonPath(
                'user.role',
                'admin'
            )
            ->assertJsonPath(
                'user.email_verified',
                true
            )
            ->assertJsonPath(
                'sessions_revoked',
                true
            );
    }

    public function test_user_identity_is_qualified_by_tenant(): void
    {
        $tenantA =
            $this->registerLandlordTestTenant();

        $tenantB =
            $this->registerLandlordTestTenant();

        $superadmin =
            $this->createTestLandlordUser(
                role: 'superadmin',
                isActive: true
            );

        $login =
            $this->loginLandlord(
                $superadmin
            );

        $token =
            $login->json('token');

        $headers = [
            'Authorization' =>
                'Bearer '.$token,
            'Accept' =>
                'application/json',
        ];

        $tenantAAdmin =
            $this
                ->withHeaders($headers)
                ->getJson(
                    '/api/landlord/users'
                    .'?tenant_id='
                    .$tenantA['tenant']->id
                )
                ->assertOk()
                ->json('users.data.0');

        $tenantBAdmin =
            $this
                ->withHeaders($headers)
                ->getJson(
                    '/api/landlord/users'
                    .'?tenant_id='
                    .$tenantB['tenant']->id
                )
                ->assertOk()
                ->json('users.data.0');

        $this->assertSame(
            $tenantAAdmin['id'],
            $tenantBAdmin['id']
        );

        $this->assertNotSame(
            $tenantAAdmin['user_key'],
            $tenantBAdmin['user_key']
        );

        $this
            ->withHeaders($headers)
            ->putJson(
                '/api/landlord/users/'
                .$tenantAAdmin['id'],
                [
                    'tenant_id' =>
                        $tenantA['tenant']->id,
                    'name' =>
                        'Updated Tenant A Administrator',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'user.name',
                'Updated Tenant A Administrator'
            );

        $this
            ->withHeaders($headers)
            ->getJson(
                '/api/landlord/users/'
                .$tenantBAdmin['id']
                .'?tenant_id='
                .$tenantB['tenant']->id
            )
            ->assertOk()
            ->assertJsonMissing([
                'name' =>
                    'Updated Tenant A Administrator',
            ]);
    }

    public function test_final_active_administrator_cannot_be_demoted_suspended_or_deleted(): void
    {
        $registration =
            $this->registerLandlordTestTenant();

        $tenant =
            $registration['tenant'];

        $superadmin =
            $this->createTestLandlordUser(
                role: 'superadmin',
                isActive: true
            );

        $login =
            $this->loginLandlord(
                $superadmin
            );

        $token =
            $login->json('token');

        $headers = [
            'Authorization' =>
                'Bearer '.$token,
            'Accept' =>
                'application/json',
        ];

        $administrator =
            $this
                ->withHeaders($headers)
                ->getJson(
                    '/api/landlord/users'
                    .'?tenant_id='
                    .$tenant->id
                )
                ->assertOk()
                ->json('users.data.0');

        $administratorId =
            $administrator['id'];

        $this
            ->withHeaders($headers)
            ->putJson(
                '/api/landlord/users/'
                .$administratorId,
                [
                    'tenant_id' =>
                        $tenant->id,
                    'role' =>
                        'doctor',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'user'
            );

        $this
            ->withHeaders($headers)
            ->putJson(
                '/api/landlord/users/'
                .$administratorId,
                [
                    'tenant_id' =>
                        $tenant->id,
                    'is_active' =>
                        false,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'user'
            );

        $this
            ->withHeaders($headers)
            ->deleteJson(
                '/api/landlord/users/'
                .$administratorId,
                [
                    'tenant_id' =>
                        $tenant->id,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'user'
            );
    }

    public function test_superadmin_can_reset_a_tenant_user_password(): void
    {
        $registration =
            $this->registerLandlordTestTenant();

        $tenant =
            $registration['tenant'];

        $superadmin =
            $this->createTestLandlordUser(
                role: 'superadmin',
                isActive: true
            );

        $login =
            $this->loginLandlord(
                $superadmin
            );

        $token =
            $login->json('token');

        $headers = [
            'Authorization' =>
                'Bearer '.$token,
            'Accept' =>
                'application/json',
        ];

        $administrator =
            $this
                ->withHeaders($headers)
                ->getJson(
                    '/api/landlord/users'
                    .'?tenant_id='
                    .$tenant->id
                )
                ->assertOk()
                ->json('users.data.0');

        $password =
            'ResetPassword123!';

        $this
            ->withHeaders($headers)
            ->postJson(
                '/api/landlord/users/'
                .$administrator['id']
                .'/reset-password',
                [
                    'tenant_id' =>
                        $tenant->id,
                    'password' =>
                        $password,
                    'password_confirmation' =>
                        $password,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'user.tenant_id',
                $tenant->id
            )
            ->assertJsonPath(
                'user.id',
                $administrator['id']
            );
    }
}