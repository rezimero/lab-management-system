<?php

namespace Tests\Feature\Tenant\Portal;

use App\Models\PortalAccessToken;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Tenant\PortalAuthenticationTestCase;

class TenantPortalAuthenticationFoundationTest extends PortalAuthenticationTestCase
{
    public function test_patient_portal_account_can_login_read_identity_and_logout(): void
    {
        $tenant = $this->registerTestTenant('portal-patient');
        $identity = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );

        $login = $this->postJson(
            '/api/portal/patient/login',
            [
                'domain' => $tenant['domain'],
                'email' => strtoupper($identity['email']),
                'password' => $identity['password'],
            ]
        );

        $login
            ->assertOk()
            ->assertJsonPath('portal', 'patient')
            ->assertJsonPath(
                'account.email',
                $identity['email']
            )
            ->assertJsonPath(
                'patient.medical_record_number',
                $identity['patient']
                    ->medical_record_number
            );

        $token = $login->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $headers = $this->portalHeaders(
            $token,
            $tenant['domain']
        );

        $this->withHeaders($headers)
            ->getJson('/api/portal/patient/me')
            ->assertOk()
            ->assertJsonPath('portal', 'patient')
            ->assertJsonPath(
                'account.uuid',
                $identity['account']->uuid
            );

        $this->withHeaders($headers)
            ->postJson('/api/portal/patient/logout')
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Logged out successfully.'
            );

        $this->withHeaders($headers)
            ->getJson('/api/portal/patient/me')
            ->assertUnauthorized();
    }

    public function test_clinician_portal_account_can_login_read_identity_and_logout(): void
    {
        $tenant = $this->registerTestTenant('portal-clinician');
        $identity = $this->createClinicianPortalIdentity(
            $tenant['tenant']
        );

        $login = $this->postJson(
            '/api/portal/clinician/login',
            [
                'domain' => $tenant['domain'],
                'email' => $identity['email'],
                'password' => $identity['password'],
            ]
        );

        $login
            ->assertOk()
            ->assertJsonPath('portal', 'clinician')
            ->assertJsonPath(
                'account.email',
                $identity['email']
            )
            ->assertJsonPath(
                'clinician.uuid',
                $identity['clinician']->uuid
            );

        $token = $login->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $headers = $this->portalHeaders(
            $token,
            $tenant['domain']
        );

        $this->withHeaders($headers)
            ->getJson('/api/portal/clinician/me')
            ->assertOk()
            ->assertJsonPath('portal', 'clinician')
            ->assertJsonPath(
                'account.uuid',
                $identity['account']->uuid
            );

        $this->withHeaders($headers)
            ->postJson('/api/portal/clinician/logout')
            ->assertOk();

        $this->withHeaders($headers)
            ->getJson('/api/portal/clinician/me')
            ->assertUnauthorized();
    }

    public function test_portal_login_requires_tenant_context(): void
    {
        $this->postJson(
            '/api/portal/patient/login',
            [
                'email' => 'patient@example.test',
                'password' => 'PortalPassword123!',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Tenant domain is required.'
            );
    }

    public function test_portal_foundation_tables_live_in_tenant_database(): void
    {
        $tenant = $this->registerTestTenant('portal-schema');

        $this->runInPortalTenant(
            $tenant['tenant'],
            function (): void {
                foreach (
                    [
                        'referring_clinicians',
                        'patient_portal_accounts',
                        'clinician_portal_accounts',
                        'portal_access_tokens',
                        'portal_invitations',
                        'portal_audit_events',
                    ] as $table
                ) {
                    $this->assertTrue(
                        Schema::connection('tenant')
                            ->hasTable($table),
                        'Missing tenant portal table: '.$table
                    );
                }
            }
        );

        $this->assertFalse(
            Schema::connection('landlord')
                ->hasTable('patient_portal_accounts')
        );
        $this->assertFalse(
            Schema::connection('landlord')
                ->hasTable('clinician_portal_accounts')
        );
        $this->assertFalse(
            Schema::connection('landlord')
                ->hasTable('portal_access_tokens')
        );
    }

    public function test_inactive_or_unactivated_accounts_cannot_login(): void
    {
        $tenant = $this->registerTestTenant('portal-inactive');

        $inactivePatient = $this->createPatientPortalIdentity(
            $tenant['tenant'],
            accountOverrides: [
                'is_active' => false,
            ]
        );

        $this->postJson(
            '/api/portal/patient/login',
            [
                'domain' => $tenant['domain'],
                'email' => $inactivePatient['email'],
                'password' => $inactivePatient['password'],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.email.0',
                'Invalid credentials.'
            );

        $unactivatedClinician = $this->createClinicianPortalIdentity(
            $tenant['tenant'],
            accountOverrides: [
                'activated_at' => null,
            ]
        );

        $this->postJson(
            '/api/portal/clinician/login',
            [
                'domain' => $tenant['domain'],
                'email' => $unactivatedClinician['email'],
                'password' => $unactivatedClinician['password'],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.email.0',
                'Invalid credentials.'
            );
    }

    public function test_expired_and_revoked_portal_tokens_are_rejected(): void
    {
        $tenant = $this->registerTestTenant('portal-token-state');
        $identity = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );

        $firstLogin = $this->postJson(
            '/api/portal/patient/login',
            [
                'domain' => $tenant['domain'],
                'email' => $identity['email'],
                'password' => $identity['password'],
            ]
        )->assertOk();

        $expiredToken = (string) $firstLogin->json('token');

        $this->runInPortalTenant(
            $tenant['tenant'],
            function () use ($expiredToken): void {
                PortalAccessToken::query()
                    ->where(
                        'token_hash',
                        hash('sha256', $expiredToken)
                    )
                    ->firstOrFail()
                    ->forceFill([
                        'expires_at' => now()->subMinute(),
                    ])
                    ->save();
            }
        );

        $this->withHeaders(
            $this->portalHeaders(
                $expiredToken,
                $tenant['domain']
            )
        )
            ->getJson('/api/portal/patient/me')
            ->assertUnauthorized();

        $secondLogin = $this->postJson(
            '/api/portal/patient/login',
            [
                'domain' => $tenant['domain'],
                'email' => $identity['email'],
                'password' => $identity['password'],
            ]
        )->assertOk();

        $revokedToken = (string) $secondLogin->json('token');

        $this->runInPortalTenant(
            $tenant['tenant'],
            function () use ($revokedToken): void {
                PortalAccessToken::query()
                    ->where(
                        'token_hash',
                        hash('sha256', $revokedToken)
                    )
                    ->firstOrFail()
                    ->forceFill([
                        'revoked_at' => now(),
                    ])
                    ->save();
            }
        );

        $this->withHeaders(
            $this->portalHeaders(
                $revokedToken,
                $tenant['domain']
            )
        )
            ->getJson('/api/portal/patient/me')
            ->assertUnauthorized();
    }
}
