<?php

namespace Tests\Feature\Tenant\Portal;

use App\Events\Tenant\Portal\ClinicianPortalInvitationCreated;
use App\Events\Tenant\Portal\PatientPortalInvitationCreated;
use App\Models\ClinicianPortalAccount;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\PortalAccessToken;
use App\Models\PortalAuditEvent;
use App\Models\PortalInvitation;
use App\Models\ReferringClinician;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Tenant\UserManagementTestCase;

class TenantPortalAccessAdministrationTest extends UserManagementTestCase
{
    public function test_administrator_can_invite_and_activate_patient_account(): void
    {
        Event::fake([
            PatientPortalInvitationCreated::class,
        ]);

        $tenant = $this->registerTestTenant(
            'patient-portal-admin'
        );
        $token = $this->loginRegisteredTenant($tenant);
        $headers = $this->tenantHeaders(
            $token,
            $tenant['domain']
        );

        $patient = $this->runInTenant(
            $tenant['tenant'],
            fn (): Patient => Patient::query()->create([
                'first_name' => 'Ada',
                'last_name' => 'Patient',
                'date_of_birth' => '1991-03-10',
                'gender' => 'female',
                'email' => 'ada.patient@example.test',
                'phone' => '+2348000000010',
                'address' => 'Test address',
                'medical_record_number' => 'MRN-PORTAL-ADA',
                'notes' => null,
                'is_active' => true,
            ])
        );

        $issued = $this->withHeaders($headers)
            ->postJson(
                '/api/portal-access/patients/'
                .$patient->getKey()
                .'/invitations',
                [
                    'email' => 'ADA.PORTAL@EXAMPLE.TEST',
                ]
            )
            ->assertCreated()
            ->assertJsonPath(
                'account.email',
                'ada.portal@example.test'
            )
            ->assertJsonPath(
                'account.activated_at',
                null
            )
            ->assertJsonPath(
                'activation.tenant_domain',
                $tenant['domain']
            );

        $plainTextToken = (string) $issued->json(
            'activation.token'
        );
        $accountUuid = (string) $issued->json(
            'account.uuid'
        );

        $this->assertGreaterThanOrEqual(
            80,
            strlen($plainTextToken)
        );

        $this->runInTenant(
            $tenant['tenant'],
            function () use (
                $plainTextToken,
                $accountUuid
            ): void {
                $account = PatientPortalAccount::query()
                    ->where('uuid', $accountUuid)
                    ->firstOrFail();
                $invitation = PortalInvitation::query()
                    ->where(
                        'account_type',
                        PortalInvitation::ACCOUNT_PATIENT
                    )
                    ->where(
                        'account_id',
                        $account->getKey()
                    )
                    ->firstOrFail();

                $this->assertSame(
                    hash('sha256', $plainTextToken),
                    $invitation->getRawOriginal(
                        'token_hash'
                    )
                );
                $this->assertNotSame(
                    $plainTextToken,
                    $invitation->getRawOriginal(
                        'token_hash'
                    )
                );
                $this->assertTrue(
                    PortalAuditEvent::query()
                        ->where(
                            'event',
                            'portal_account_created'
                        )
                        ->where(
                            'subject_uuid',
                            $accountUuid
                        )
                        ->exists()
                );
            }
        );

        Event::assertDispatched(
            PatientPortalInvitationCreated::class,
            fn (PatientPortalInvitationCreated $event): bool => (
                $event->tenantId === $tenant['tenant']->id
                && $event->accountUuid === $accountUuid
                && $event->recipientEmail
                    === 'ada.portal@example.test'
            )
        );

        $portalHeaders = [
            'Accept' => 'application/json',
            'X-Tenant-Domain' => $tenant['domain'],
        ];

        $this->withHeaders($portalHeaders)
            ->postJson('/api/portal/activation/inspect', [
                'token' => $plainTextToken,
            ])
            ->assertOk()
            ->assertJsonPath('account_type', 'patient')
            ->assertJsonPath(
                'display_name',
                'Ada Patient'
            );

        $password = 'ActivatedPortal123!';

        $this->withHeaders($portalHeaders)
            ->postJson('/api/portal/activation/complete', [
                'token' => $plainTextToken,
                'password' => $password,
                'password_confirmation' => $password,
            ])
            ->assertOk()
            ->assertJsonPath(
                'account_type',
                'patient'
            );

        $this->withHeaders($portalHeaders)
            ->postJson('/api/portal/activation/inspect', [
                'token' => $plainTextToken,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->postJson('/api/portal/patient/login', [
            'domain' => $tenant['domain'],
            'email' => 'ada.portal@example.test',
            'password' => $password,
        ])
            ->assertOk()
            ->assertJsonPath(
                'account.uuid',
                $accountUuid
            );
    }

    public function test_replaced_invitation_is_revoked_and_clinician_account_can_be_disabled(): void
    {
        Event::fake([
            ClinicianPortalInvitationCreated::class,
        ]);

        $tenant = $this->registerTestTenant(
            'clinician-portal-admin'
        );
        $token = $this->loginRegisteredTenant($tenant);
        $headers = $this->tenantHeaders(
            $token,
            $tenant['domain']
        );

        $clinician = $this->runInTenant(
            $tenant['tenant'],
            fn (): ReferringClinician => (
                ReferringClinician::query()->create([
                    'name' => 'Dr Portal Clinician',
                    'email' => 'clinician@example.test',
                    'registration_number' => 'MDCN-PORTAL-1',
                    'is_active' => true,
                ])
            )
        );

        $endpoint = '/api/portal-access/clinicians/'
            .$clinician->uuid
            .'/invitations';

        $first = $this->withHeaders($headers)
            ->postJson($endpoint, [
                'email' => 'clinician.portal@example.test',
            ])
            ->assertCreated();

        $firstToken = (string) $first->json(
            'activation.token'
        );

        $second = $this->withHeaders($headers)
            ->postJson($endpoint, [
                'email' => 'clinician.portal@example.test',
            ])
            ->assertCreated();

        $secondToken = (string) $second->json(
            'activation.token'
        );
        $accountUuid = (string) $second->json(
            'account.uuid'
        );

        $this->assertNotSame($firstToken, $secondToken);

        $portalHeaders = [
            'Accept' => 'application/json',
            'X-Tenant-Domain' => $tenant['domain'],
        ];

        $this->withHeaders($portalHeaders)
            ->postJson('/api/portal/activation/inspect', [
                'token' => $firstToken,
            ])
            ->assertUnprocessable();

        $password = 'ClinicianPortal123!';

        $this->withHeaders($portalHeaders)
            ->postJson('/api/portal/activation/complete', [
                'token' => $secondToken,
                'password' => $password,
                'password_confirmation' => $password,
            ])
            ->assertOk();

        $login = $this->postJson(
            '/api/portal/clinician/login',
            [
                'domain' => $tenant['domain'],
                'email' => 'clinician.portal@example.test',
                'password' => $password,
            ]
        )->assertOk();

        $portalToken = (string) $login->json('token');

        $this->withHeaders($headers)
            ->patchJson(
                '/api/portal-access/clinician-accounts/'
                .$accountUuid
                .'/deactivate'
            )
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$portalToken,
            'X-Tenant-Domain' => $tenant['domain'],
        ])
            ->getJson('/api/portal/clinician/me')
            ->assertUnauthorized();

        $this->runInTenant(
            $tenant['tenant'],
            function () use ($accountUuid): void {
                $account = ClinicianPortalAccount::query()
                    ->where('uuid', $accountUuid)
                    ->firstOrFail();

                $this->assertTrue(
                    PortalAccessToken::query()
                        ->where(
                            'account_type',
                            PortalAccessToken::ACCOUNT_CLINICIAN
                        )
                        ->where(
                            'account_id',
                            $account->getKey()
                        )
                        ->whereNotNull('revoked_at')
                        ->exists()
                );
            }
        );
    }

    public function test_non_administrator_cannot_manage_portal_access(): void
    {
        $tenant = $this->registerTestTenant(
            'portal-admin-permission'
        );
        $user = $this->createTenantUser(
            $tenant['tenant'],
            [
                'email' => 'portal-reception@example.test',
                'password' => 'Password123!',
                'role' => 'receptionist',
            ]
        );

        $login = $this->postJson('/api/login', [
            'domain' => $tenant['domain'],
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertOk();

        $this->withHeaders(
            $this->tenantHeaders(
                (string) $login->json('token'),
                $tenant['domain']
            )
        )
            ->getJson('/api/portal-access/patients')
            ->assertForbidden();
    }

    public function test_portal_audit_table_is_tenant_owned(): void
    {
        $tenant = $this->registerTestTenant(
            'portal-audit-schema'
        );

        $this->runInTenant(
            $tenant['tenant'],
            function (): void {
                $this->assertTrue(
                    Schema::connection('tenant')
                        ->hasTable('portal_audit_events')
                );
            }
        );

        $this->assertFalse(
            Schema::connection('landlord')
                ->hasTable('portal_audit_events')
        );
    }
}
