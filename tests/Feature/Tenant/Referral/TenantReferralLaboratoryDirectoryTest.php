<?php

namespace Tests\Feature\Tenant\Referral;

use App\Models\Plan;
use App\Models\TenantSubscription;
use App\Support\Commercial\CommercialFeatureCatalog;
use Illuminate\Support\Str;
use Tests\Support\Tenant\ReferralManagementTestCase;

class TenantReferralLaboratoryDirectoryTest extends ReferralManagementTestCase
{
    public function test_administrator_manages_laboratories_and_test_mappings(): void
    {
        $context = $this->authenticatedReferralContext(
            'referral-directory-admin'
        );
        $labTest = $this->createLabTest($context['tenant'], [
            'name' => 'Specialized Molecular Test',
            'category' => 'Molecular',
            'is_active' => true,
        ]);

        $laboratory = $this->withHeaders($context['headers'])
            ->postJson('/api/referrals/laboratories', [
                'code' => ' ext-lab ',
                'name' => ' External Reference Laboratory ',
                'accreditation_number' => 'ISO-15189',
                'contact_name' => 'Dr Reference',
                'email' => 'reference@example.test',
                'phone' => '08000000001',
                'address_line_1' => '1 Referral Avenue',
                'city' => 'Benin City',
                'state' => 'Edo',
                'country' => 'Nigeria',
                'currency' => 'ngn',
                'default_turnaround_hours' => 72,
                'notes' => 'Primary referral partner.',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'EXT-LAB')
            ->assertJsonPath('data.currency', 'NGN')
            ->assertJsonPath('data.name', 'External Reference Laboratory');

        $laboratoryUuid = $laboratory->json('data.uuid');

        $mapping = $this->withHeaders($context['headers'])
            ->postJson(
                '/api/referrals/laboratories/'
                .$laboratoryUuid
                .'/tests',
                [
                    'lab_test_uuid' => $labTest->uuid,
                    'external_test_code' => ' mol-ext ',
                    'external_test_name' => ' Molecular External Assay ',
                    'cost' => '3500.50',
                    'currency' => 'ngn',
                    'turnaround_hours' => 48,
                    'specimen_requirements' => [
                        'specimen_type' => ' Plasma ',
                        'container' => ' EDTA tube ',
                        'minimum_volume' => ' 2 mL ',
                        'instructions' => ' Keep refrigerated. ',
                    ],
                    'is_active' => true,
                ]
            )
            ->assertCreated()
            ->assertJsonPath('data.external_test_code', 'MOL-EXT')
            ->assertJsonPath(
                'data.external_test_name',
                'Molecular External Assay'
            )
            ->assertJsonPath('data.cost', '3500.50')
            ->assertJsonPath('data.lab_test.uuid', $labTest->uuid)
            ->assertJsonPath(
                'data.specimen_requirements.minimum_volume',
                '2 mL'
            );

        $mappingUuid = $mapping->json('data.uuid');

        $this->withHeaders($context['headers'])
            ->getJson('/api/referrals/laboratories?search=External')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $laboratoryUuid)
            ->assertJsonPath('data.0.mapped_tests_count', 1);

        $this->withHeaders($context['headers'])
            ->getJson(
                '/api/referrals/laboratories/'
                .$laboratoryUuid
            )
            ->assertOk()
            ->assertJsonPath('data.test_mappings.0.uuid', $mappingUuid);

        $this->withHeaders($context['headers'])
            ->putJson(
                '/api/referrals/laboratory-tests/'.$mappingUuid,
                [
                    'lab_test_uuid' => $labTest->uuid,
                    'external_test_code' => 'MOL-EXT-UPDATED',
                    'external_test_name' => 'Updated Molecular Assay',
                    'cost' => '3600.00',
                    'currency' => 'NGN',
                    'turnaround_hours' => 24,
                    'is_active' => false,
                ]
            )
            ->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.cost', '3600.00');
    }

    public function test_view_roles_can_read_but_cannot_manage_directory(): void
    {
        $context = $this->authenticatedReferralContext(
            'referral-directory-permissions'
        );
        $labTest = $this->createLabTest($context['tenant']);
        $directory = $this->createReferralDirectory(
            $context['headers'],
            $labTest,
            'PERMISSIONS'
        );
        $technicianHeaders = $this->headersForTenantUser(
            $context['tenant'],
            $context['registration']['domain'],
            'lab_technician'
        );

        $this->withHeaders($technicianHeaders)
            ->getJson('/api/referrals/laboratories')
            ->assertOk()
            ->assertJsonPath(
                'data.0.uuid',
                $directory['laboratory_uuid']
            );

        $this->withHeaders($technicianHeaders)
            ->postJson('/api/referrals/laboratories', [
                'code' => 'FORBIDDEN',
                'name' => 'Forbidden Laboratory',
                'currency' => 'NGN',
            ])
            ->assertForbidden();

        $this->assertSame(1, $this->tenantReferralCount(
            $context['tenant'],
            'referral_laboratories'
        ));
    }

    public function test_inactive_laboratory_or_local_test_blocks_active_mapping(): void
    {
        $context = $this->authenticatedReferralContext(
            'referral-directory-active-guards'
        );
        $activeTest = $this->createLabTest($context['tenant']);
        $inactiveTest = $this->createLabTest($context['tenant'], [
            'is_active' => false,
        ]);
        $laboratoryUuid = $this->withHeaders($context['headers'])
            ->postJson('/api/referrals/laboratories', [
                'code' => 'INACTIVE-LAB',
                'name' => 'Inactive Referral Laboratory',
                'currency' => 'NGN',
                'is_active' => false,
            ])
            ->assertCreated()
            ->json('data.uuid');

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/referrals/laboratories/'
                .$laboratoryUuid
                .'/tests',
                [
                    'lab_test_uuid' => $activeTest->uuid,
                    'external_test_code' => 'ACTIVE-TEST',
                    'external_test_name' => 'Active Test',
                    'cost' => '100.00',
                    'currency' => 'NGN',
                    'is_active' => true,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');

        $activeLaboratoryUuid = $this->withHeaders($context['headers'])
            ->postJson('/api/referrals/laboratories', [
                'code' => 'ACTIVE-LAB',
                'name' => 'Active Referral Laboratory',
                'currency' => 'NGN',
                'is_active' => true,
            ])
            ->assertCreated()
            ->json('data.uuid');

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/referrals/laboratories/'
                .$activeLaboratoryUuid
                .'/tests',
                [
                    'lab_test_uuid' => $inactiveTest->uuid,
                    'external_test_code' => 'INACTIVE-TEST',
                    'external_test_name' => 'Inactive Test',
                    'cost' => '100.00',
                    'currency' => 'NGN',
                    'is_active' => true,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lab_test_uuid');

        $this->assertSame(0, $this->tenantReferralCount(
            $context['tenant'],
            'referral_laboratory_tests'
        ));
    }

    public function test_duplicate_local_test_mapping_is_rejected(): void
    {
        $context = $this->authenticatedReferralContext(
            'referral-directory-duplicate'
        );
        $labTest = $this->createLabTest($context['tenant']);
        $directory = $this->createReferralDirectory(
            $context['headers'],
            $labTest,
            'DUPLICATE'
        );

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/referrals/laboratories/'
                .$directory['laboratory_uuid']
                .'/tests',
                [
                    'lab_test_uuid' => $labTest->uuid,
                    'external_test_code' => 'SECOND',
                    'external_test_name' => 'Second Mapping',
                    'cost' => '200.00',
                    'currency' => 'NGN',
                    'is_active' => true,
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lab_test_uuid');

        $this->assertSame(1, $this->tenantReferralCount(
            $context['tenant'],
            'referral_laboratory_tests'
        ));
    }

    public function test_disabled_entitlement_blocks_directory_mutation_without_writes(): void
    {
        $context = $this->authenticatedReferralContext(
            'referral-directory-entitlement'
        );
        $subscription = TenantSubscription::query()
            ->where('tenant_id', $context['tenant']->getKey())
            ->latest('starts_at')
            ->firstOrFail();
        $originalPlanId = $subscription->plan_id;
        $plan = Plan::query()->create([
            'code' => 'referrals-disabled-'.strtolower(Str::random(8)),
            'name' => 'Referrals Disabled Test Plan',
            'currency' => 'NGN',
            'monthly_price' => '0.00',
            'annual_price' => '0.00',
            'trial_days' => 0,
            'is_default' => false,
            'is_public' => false,
            'is_active' => true,
            'sort_order' => 999,
        ]);
        $plan->features()->create([
            'feature_key' => CommercialFeatureCatalog::REFERRAL_LABS,
            'name' => 'Referral laboratories',
            'is_enabled' => false,
        ]);
        $subscription->update(['plan_id' => $plan->getKey()]);

        try {
            $this->withHeaders($context['headers'])
                ->postJson('/api/referrals/laboratories', [
                    'code' => 'BLOCKED',
                    'name' => 'Blocked Referral Laboratory',
                    'currency' => 'NGN',
                ])
                ->assertForbidden();

            $this->assertSame(0, $this->tenantReferralCount(
                $context['tenant'],
                'referral_laboratories'
            ));
        } finally {
            $this->restoreLandlordContext();
            $subscription->refresh()->update([
                'plan_id' => $originalPlanId,
            ]);
            $plan->delete();
        }
    }

    public function test_directory_records_are_isolated_between_tenants(): void
    {
        $first = $this->authenticatedReferralContext(
            'referral-directory-tenant-a'
        );
        $labTest = $this->createLabTest($first['tenant']);
        $directory = $this->createReferralDirectory(
            $first['headers'],
            $labTest,
            'TENANT-A'
        );

        $second = $this->authenticatedReferralContext(
            'referral-directory-tenant-b'
        );

        $this->withHeaders($second['headers'])
            ->getJson(
                '/api/referrals/laboratories/'
                .$directory['laboratory_uuid']
            )
            ->assertNotFound();

        $this->assertSame(0, $this->tenantReferralCount(
            $second['tenant'],
            'referral_laboratories'
        ));
    }
}
