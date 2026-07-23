<?php

namespace Tests\Feature\Tenant\User;

use Illuminate\Support\Facades\Hash;
use Tests\Support\Tenant\UserManagementTestCase;

class TenantUserSelfServiceTest extends
    UserManagementTestCase
{
    public function test_administrator_can_update_own_name_and_email_with_current_password(): void
    {
        $registration =
            $this->registerTestTenant(
                'self-profile'
            );

        $token =
            $this->loginRegisteredTenant(
                $registration
            );

        $administrator =
            $this->findTenantUserByEmail(
                $registration['tenant'],
                $registration['email']
            );

        $this->assertNotNull(
            $administrator
        );

        $newEmail =
            'updated-admin@example.test';

        $response =
            $this
                ->withHeaders(
                    $this->tenantHeaders(
                        $token,
                        $registration[
                            'domain'
                        ]
                    )
                )
                ->putJson(
                    '/api/users/'
                    .$administrator->id,
                    [
                        'name' =>
                            'Updated Tenant Administrator',
                        'email' =>
                            $newEmail,
                        'current_password' =>
                            $registration[
                                'password'
                            ],
                    ]
                );

        $response
            ->assertOk()
            ->assertJsonPath(
                'self_service',
                true
            )
            ->assertJsonPath(
                'user.name',
                'Updated Tenant Administrator'
            )
            ->assertJsonPath(
                'user.email',
                $newEmail
            )
            ->assertJsonPath(
                'user.role',
                'admin'
            )
            ->assertJsonPath(
                'user.is_active',
                true
            );

        $updated =
            $this->findTenantUserByEmail(
                $registration['tenant'],
                $newEmail
            );

        $this->assertNotNull(
            $updated
        );

        $this->assertSame(
            'admin',
            $updated->role
        );

        $this->assertTrue(
            (bool) $updated->is_active
        );
    }

    public function test_email_change_rejects_an_incorrect_current_password(): void
    {
        $registration =
            $this->registerTestTenant(
                'self-email-password'
            );

        $token =
            $this->loginRegisteredTenant(
                $registration
            );

        $administrator =
            $this->findTenantUserByEmail(
                $registration['tenant'],
                $registration['email']
            );

        $this->assertNotNull(
            $administrator
        );

        $this
            ->withHeaders(
                $this->tenantHeaders(
                    $token,
                    $registration[
                        'domain'
                    ]
                )
            )
            ->putJson(
                '/api/users/'
                .$administrator->id,
                [
                    'email' =>
                        'should-not-change@example.test',
                    'current_password' =>
                        'IncorrectPassword!',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'current_password'
            );

        $this->assertNotNull(
            $this->findTenantUserByEmail(
                $registration['tenant'],
                $registration['email']
            )
        );
    }

    public function test_administrator_can_change_own_password_with_current_password_and_confirmation(): void
    {
        $registration =
            $this->registerTestTenant(
                'self-password'
            );

        $token =
            $this->loginRegisteredTenant(
                $registration
            );

        $administrator =
            $this->findTenantUserByEmail(
                $registration['tenant'],
                $registration['email']
            );

        $this->assertNotNull(
            $administrator
        );

        $newPassword =
            'NewSecurePassword123!';

        $this
            ->withHeaders(
                $this->tenantHeaders(
                    $token,
                    $registration[
                        'domain'
                    ]
                )
            )
            ->putJson(
                '/api/users/'
                .$administrator->id,
                [
                    'password' =>
                        $newPassword,
                    'password_confirmation' =>
                        $newPassword,
                    'current_password' =>
                        $registration[
                            'password'
                        ],
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'self_service',
                true
            )
            ->assertJsonPath(
                'sessions_revoked',
                true
            );

        $updated =
            $this->findTenantUserByEmail(
                $registration['tenant'],
                $registration['email']
            );

        $this->assertNotNull(
            $updated
        );

        $this->assertTrue(
            Hash::check(
                $newPassword,
                $updated->password
            )
        );

        $this->assertFalse(
            Hash::check(
                $registration[
                    'password'
                ],
                $updated->password
            )
        );

        $this
            ->postJson(
                '/api/login',
                [
                    'domain' =>
                        $registration[
                            'domain'
                        ],
                    'email' =>
                        $registration[
                            'email'
                        ],
                    'password' =>
                        $newPassword,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'user.role',
                'admin'
            );
    }

    public function test_administrator_cannot_demote_suspend_or_delete_own_account(): void
    {
        $registration =
            $this->registerTestTenant(
                'self-protection'
            );

        $token =
            $this->loginRegisteredTenant(
                $registration
            );

        $administrator =
            $this->findTenantUserByEmail(
                $registration['tenant'],
                $registration['email']
            );

        $this->assertNotNull(
            $administrator
        );

        $headers =
            $this->tenantHeaders(
                $token,
                $registration['domain']
            );

        $this
            ->withHeaders($headers)
            ->putJson(
                '/api/users/'
                .$administrator->id,
                [
                    'role' =>
                        'doctor',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'role'
            );

        $this
            ->withHeaders($headers)
            ->putJson(
                '/api/users/'
                .$administrator->id,
                [
                    'is_active' =>
                        false,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'is_active'
            );

        $this
            ->withHeaders($headers)
            ->deleteJson(
                '/api/users/'
                .$administrator->id
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'user'
            );

        $protectedAdministrator =
            $this->findTenantUserByEmail(
                $registration['tenant'],
                $registration['email']
            );

        $this->assertNotNull(
            $protectedAdministrator
        );

        $this->assertSame(
            'admin',
            $protectedAdministrator->role
        );

        $this->assertTrue(
            (bool) $protectedAdministrator
                ->is_active
        );
    }

    public function test_non_administrator_cannot_manage_another_user(): void
    {
        $registration =
            $this->registerTestTenant(
                'user-management-access'
            );

        $doctor =
            $this->createTenantUser(
                $registration['tenant'],
                [
                    'name' =>
                        'Restricted Doctor',
                    'email' =>
                        'restricted-doctor@example.test',
                    'password' =>
                        'DoctorPassword123!',
                    'role' =>
                        'doctor',
                ]
            );

        $target =
            $this->createTenantUser(
                $registration['tenant'],
                [
                    'name' =>
                        'Target User',
                    'role' =>
                        'receptionist',
                ]
            );

        $login =
            $this->postJson(
                '/api/login',
                [
                    'domain' =>
                        $registration[
                            'domain'
                        ],
                    'email' =>
                        $doctor->email,
                    'password' =>
                        'DoctorPassword123!',
                ]
            );

        $login->assertOk();

        $doctorToken =
            $login->json('token');

        $this->assertIsString(
            $doctorToken
        );

        $this
            ->withHeaders(
                $this->tenantHeaders(
                    $doctorToken,
                    $registration[
                        'domain'
                    ]
                )
            )
            ->putJson(
                '/api/users/'
                .$target->id,
                [
                    'name' =>
                        'Unauthorised Change',
                ]
            )
            ->assertForbidden();

        $unchangedTarget =
            $this->findTenantUserByEmail(
                $registration['tenant'],
                $target->email
            );

        $this->assertNotNull(
            $unchangedTarget
        );

        $this->assertSame(
            'Target User',
            $unchangedTarget->name
        );
    }
}