<?php

namespace Tests\Feature\Tenant\Portal;

use App\Models\ReferringClinician;
use Tests\Support\Tenant\UserManagementTestCase;

class TenantReferringClinicianDirectoryTest extends UserManagementTestCase
{
    public function test_administrator_can_manage_referring_clinician_directory(): void
    {
        $tenant = $this->registerTestTenant('clinician-directory');
        $token = $this->loginRegisteredTenant($tenant);

        $headers = $this->tenantHeaders(
            $token,
            $tenant['domain']
        );

        $created = $this->withHeaders($headers)
            ->postJson('/api/referring-clinicians', [
                'name' => '  Dr Ada Mensah  ',
                'email' => ' ADA.MENSAH@EXAMPLE.TEST ',
                'phone' => ' 08012345678 ',
                'organisation' => ' City Medical Centre ',
                'registration_number' => ' MDCN-1001 ',
                'specialty' => ' Internal Medicine ',
            ]);

        $created
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Referring clinician created successfully.'
            )
            ->assertJsonPath('data.name', 'Dr Ada Mensah')
            ->assertJsonPath(
                'data.email',
                'ada.mensah@example.test'
            )
            ->assertJsonPath('data.is_active', true);

        $uuid = (string) $created->json('data.uuid');

        $this->withHeaders($headers)
            ->getJson(
                '/api/referring-clinicians'
                .'?search=City'
                .'&is_active=1'
                .'&per_page=25'
            )
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $uuid);

        $this->withHeaders($headers)
            ->putJson(
                '/api/referring-clinicians/'.$uuid,
                [
                    'name' => 'Dr Ada Mensah',
                    'email' => 'ada.mensah@example.test',
                    'phone' => '08012345678',
                    'organisation' => 'City Specialist Centre',
                    'registration_number' => 'MDCN-1001',
                    'specialty' => 'Cardiology',
                    'is_active' => true,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.organisation',
                'City Specialist Centre'
            )
            ->assertJsonPath(
                'data.specialty',
                'Cardiology'
            );

        $this->withHeaders($headers)
            ->patchJson(
                '/api/referring-clinicians/'
                .$uuid
                .'/deactivate'
            )
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->withHeaders($headers)
            ->patchJson(
                '/api/referring-clinicians/'
                .$uuid
                .'/activate'
            )
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
    }

    public function test_receptionist_can_view_but_cannot_manage_directory(): void
    {
        $tenant = $this->registerTestTenant(
            'clinician-directory-role'
        );

        $user = $this->createTenantUser(
            $tenant['tenant'],
            [
                'name' => 'Portal Receptionist',
                'email' => 'receptionist@example.test',
                'password' => 'Password123!',
                'role' => 'receptionist',
                'is_active' => true,
            ]
        );

        $this->runInTenant(
            $tenant['tenant'],
            function (): void {
                ReferringClinician::query()->create([
                    'name' => 'Dr Read Only',
                    'email' => 'readonly@example.test',
                    'is_active' => true,
                ]);
            }
        );

        $login = $this->postJson('/api/login', [
            'domain' => $tenant['domain'],
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertOk();

        $headers = $this->tenantHeaders(
            (string) $login->json('token'),
            $tenant['domain']
        );

        $this->withHeaders($headers)
            ->getJson('/api/referring-clinicians')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->withHeaders($headers)
            ->postJson('/api/referring-clinicians', [
                'name' => 'Dr Forbidden',
            ])
            ->assertForbidden();
    }

    public function test_directory_is_tenant_scoped(): void
    {
        $tenantA = $this->registerTestTenant(
            'clinician-directory-a'
        );
        $tenantB = $this->registerTestTenant(
            'clinician-directory-b'
        );

        $tokenA = $this->loginRegisteredTenant($tenantA);

        $clinicianA = $this->runInTenant(
            $tenantA['tenant'],
            fn (): ReferringClinician => (
                ReferringClinician::query()->create([
                    'name' => 'Dr Tenant A',
                    'email' => 'tenant-a@example.test',
                    'is_active' => true,
                ])
            )
        );

        $this->runInTenant(
            $tenantB['tenant'],
            function (): void {
                ReferringClinician::query()->create([
                    'name' => 'Dr Tenant B',
                    'email' => 'tenant-b@example.test',
                    'is_active' => true,
                ]);
            }
        );

        $this->withHeaders(
            $this->tenantHeaders(
                $tokenA,
                $tenantA['domain']
            )
        )
            ->getJson('/api/referring-clinicians')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.0.uuid',
                $clinicianA->uuid
            )
            ->assertJsonMissing([
                'name' => 'Dr Tenant B',
            ]);
    }
}
