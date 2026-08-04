<?php

namespace Tests\Support\Tenant;

use App\Models\LabTest;
use App\Models\ReferralLaboratory;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

abstract class ReferralManagementTestCase extends TestResultManagementTestCase
{
    /**
     * @return array<string, mixed>
     */
    protected function authenticatedReferralContext(
        string $prefix = 'referral'
    ): array {
        return $this->authenticatedTestResultContext($prefix);
    }

    /**
     * @return array<string, string>
     */
    protected function headersForTenantUser(
        Tenant $tenant,
        string $domain,
        string $role
    ): array {
        $password = 'Password123!';
        $user = $this->createTenantUser($tenant, [
            'name' => ucfirst(str_replace('_', ' ', $role)).' User',
            'email' => sprintf(
                '%s-%s@example.test',
                str_replace('_', '-', $role),
                strtolower(Str::random(10))
            ),
            'password' => $password,
            'role' => $role,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'domain' => $domain,
            'email' => $user->email,
            'password' => $password,
        ])->assertOk();

        $token = $response->json('token');
        $this->assertIsString($token);
        $this->restoreLandlordContext();

        return $this->tenantHeaders($token, $domain);
    }

    /**
     * @return array{laboratory_uuid: string, mapping_uuid: string}
     */
    protected function createReferralDirectory(
        array $headers,
        LabTest $labTest,
        string $suffix = 'BASE'
    ): array {
        $laboratoryUuid = $this->withHeaders($headers)
            ->postJson('/api/referrals/laboratories', [
                'code' => 'REFLAB-'.$suffix,
                'name' => 'Referral Laboratory '.$suffix,
                'accreditation_number' => 'ACC-'.$suffix,
                'currency' => 'NGN',
                'default_turnaround_hours' => 48,
                'is_active' => true,
            ])
            ->assertCreated()
            ->json('data.uuid');

        $mappingUuid = $this->withHeaders($headers)
            ->postJson(
                '/api/referrals/laboratories/'
                .$laboratoryUuid
                .'/tests',
                [
                    'lab_test_uuid' => $labTest->uuid,
                    'external_test_code' => 'EXT-'.$suffix,
                    'external_test_name' => $labTest->name.' External',
                    'cost' => '1250.00',
                    'currency' => 'NGN',
                    'turnaround_hours' => 36,
                    'specimen_requirements' => [
                        'specimen_type' => 'Serum',
                        'container' => 'Plain tube',
                    ],
                    'is_active' => true,
                ]
            )
            ->assertCreated()
            ->json('data.uuid');

        return [
            'laboratory_uuid' => $laboratoryUuid,
            'mapping_uuid' => $mappingUuid,
        ];
    }

    protected function tenantReferralCount(
        Tenant $tenant,
        string $table
    ): int {
        return $this->runInTenant(
            $tenant,
            static fn (): int => (int) DB::connection('tenant')
                ->table($table)
                ->count()
        );
    }

    protected function findReferralLaboratory(
        Tenant $tenant,
        string $uuid
    ): ReferralLaboratory {
        return $this->runInTenant(
            $tenant,
            static fn (): ReferralLaboratory => ReferralLaboratory::query()
                ->where('uuid', $uuid)
                ->firstOrFail()
        );
    }

    protected function tenantAdmin(Tenant $tenant): TenantUser
    {
        return $this->runInTenant(
            $tenant,
            static fn (): TenantUser => TenantUser::query()
                ->where('role', 'admin')
                ->firstOrFail()
        );
    }
}
