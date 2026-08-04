<?php

namespace Tests\Support\Tenant;

use App\Models\Branch;
use App\Models\LaboratoryOrder;
use App\Models\LaboratoryReferral;
use App\Models\LabTest;
use App\Models\ReferralLaboratory;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

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
     * @param  array<int, int>  $branchIds
     * @return array<string, string>
     */
    protected function headersForTenantUser(
        Tenant $tenant,
        string $domain,
        string $role,
        array $branchIds = []
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

        if ($branchIds !== []) {
            $this->runInTenant(
                $tenant,
                static function () use ($user, $branchIds): void {
                    $user->branches()->sync(
                        collect($branchIds)
                            ->mapWithKeys(
                                static fn (int $branchId): array => [
                                    $branchId => [
                                        'is_primary' => false,
                                        'assigned_by' => null,
                                    ],
                                ]
                            )
                            ->all()
                    );
                }
            );
        }

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

    protected function headOffice(Tenant $tenant): Branch
    {
        return $this->runInTenant(
            $tenant,
            static fn (): Branch => Branch::query()
                ->where('is_head_office', true)
                ->firstOrFail()
        );
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

    protected function createReferralReadyOrder(
        Tenant $tenant,
        array $headers,
        LabTest $labTest,
        ?Branch $branch = null
    ): LaboratoryOrder {
        $branch ??= $this->headOffice($tenant);
        $patient = $this->createPatient($tenant, [
            'first_name' => 'Referral',
            'last_name' => 'Patient',
        ]);
        $order = $this->createLaboratoryOrder(
            $tenant,
            $patient,
            [
                'collection_branch_id' => $branch->getKey(),
                'processing_branch_id' => $branch->getKey(),
                'status' => 'pending',
                'sample_collected_at' => null,
            ],
            [[
                'lab_test_id' => $labTest->getKey(),
                'test_name_snapshot' => $labTest->name,
                'test_category_snapshot' => $labTest->category,
                'price_snapshot' => $labTest->price,
                'turnaround_time_hours_snapshot' => (
                    $labTest->turnaround_time_hours
                ),
                'parameters_snapshot' => $labTest->parameters,
                'status' => 'pending',
            ]]
        );

        $item = $order->items->firstOrFail();

        $this->createCollectedSpecimen(
            $tenant,
            $order,
            [$item->uuid],
            [
                'status' => 'received',
                'received_at' => now(),
                'specimen_type' => 'Serum',
                'container_type' => 'Plain tube',
            ]
        );

        $this->withHeaders($headers)
            ->patchJson(
                '/api/laboratory-orders/'.$order->uuid.'/status',
                ['status' => 'in_progress']
            )
            ->assertOk();

        $this->startOrderItem(
            $headers,
            $item
        )
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        return $this->storedProcessingOrder($tenant, $order->uuid);
    }

    protected function createReferralDraft(
        array $headers,
        Tenant $tenant,
        LaboratoryOrder $order,
        string $mappingUuid,
        array $overrides = []
    ): TestResponse {
        $item = $order->items->firstOrFail();
        $specimens = $this->specimensForOrder(
            $tenant,
            $order->uuid
        );

        return $this->withHeaders($headers)
            ->postJson(
                '/api/referrals',
                array_merge(
                    [
                        'order_item_uuid' => $item->uuid,
                        'referral_laboratory_test_uuid' => $mappingUuid,
                        'specimen_uuids' => $specimens
                            ->pluck('uuid')
                            ->all(),
                    ],
                    $overrides
                )
            );
    }

    protected function storedReferral(
        Tenant $tenant,
        string $uuid
    ): LaboratoryReferral {
        return $this->runInTenant(
            $tenant,
            static fn (): LaboratoryReferral => (
                LaboratoryReferral::query()
                    ->with([
                        'orderItem.order',
                        'specimens',
                        'events',
                    ])
                    ->where('uuid', $uuid)
                    ->firstOrFail()
            )
        );
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
