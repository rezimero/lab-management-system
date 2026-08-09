<?php

namespace Tests\Feature\Tenant\Referral;

use App\Models\Branch;
use App\Models\Plan;
use App\Models\TenantSubscription;
use App\Support\Commercial\CommercialFeatureCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\Tenant\ReferralManagementTestCase;

class TenantReferralWorkflowTest extends ReferralManagementTestCase
{
    use RefreshDatabase;

    public function test_eligible_item_creates_draft_with_snapshots_and_queries(): void
    {
        $context = $this->authenticatedReferralContext(
            'referral-workflow-create'
        );
        $labTest = $this->createLabTest($context['tenant'], [
            'name' => 'Referral Molecular Assay',
            'category' => 'Molecular',
            'price' => '5000.00',
            'parameters' => $this->defaultResultParameters(),
        ]);
        $directory = $this->createReferralDirectory(
            $context['headers'],
            $labTest,
            'CREATE'
        );
        $order = $this->createReferralReadyOrder(
            $context['tenant'],
            $context['headers'],
            $labTest
        );
        $item = $order->items->firstOrFail();

        $response = $this->createReferralDraft(
            $context['headers'],
            $context['tenant'],
            $order,
            $directory['mapping_uuid'],
            [
                'external_reference' => ' EXT-REF-100 ',
                'dispatch_notes' => ' Prepared for send-out. ',
            ]
        )
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath(
                'data.order_item.uuid',
                $item->uuid
            )
            ->assertJsonPath(
                'data.laboratory.code',
                'REFLAB-CREATE'
            )
            ->assertJsonPath(
                'data.test.external_test_code',
                'EXT-CREATE'
            )
            ->assertJsonPath(
                'data.specimens.0.snapshot.status',
                'received'
            )
            ->assertJsonPath(
                'data.timeline.0.event_type',
                'created'
            )
            ->assertJsonPath(
                'data.external_reference',
                'EXT-REF-100'
            );

        $referralUuid = $response->json('data.uuid');
        $referralNumber = $response->json('data.referral_number');

        $this->assertMatchesRegularExpression(
            '/^REF-\d{8}-[A-Z0-9]{8}$/',
            $referralNumber
        );

        $stored = $this->storedReferral(
            $context['tenant'],
            $referralUuid
        );

        $this->assertSame($item->uuid, $stored->open_order_item_key);
        $this->assertCount(1, $stored->specimens);
        $this->assertCount(1, $stored->events);
        $this->assertSame('created', $stored->events->first()->event_type);

        $this->withHeaders($context['headers'])
            ->getJson('/api/referrals?status=draft')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $referralUuid);

        $this->withHeaders($context['headers'])
            ->getJson('/api/referrals/summary')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.draft', 1);

        $this->withHeaders($context['headers'])
            ->getJson('/api/referrals/'.$referralUuid)
            ->assertOk()
            ->assertJsonPath('data.timeline.0.event_type', 'created');

        $this->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-order-items/'
                .$item->uuid
                .'/referrals'
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $referralUuid);
    }

    public function test_creation_requires_in_progress_item_and_received_specimen(): void
    {
        $context = $this->authenticatedReferralContext(
            'referral-workflow-readiness'
        );
        $branch = $this->headOffice($context['tenant']);
        $labTest = $this->createLabTest($context['tenant'], [
            'name' => 'Referral Readiness Test',
        ]);
        $directory = $this->createReferralDirectory(
            $context['headers'],
            $labTest,
            'READINESS'
        );
        $patient = $this->createPatient($context['tenant']);

        $withoutSpecimen = $this->createLaboratoryOrder(
            $context['tenant'],
            $patient,
            [
                'collection_branch_id' => $branch->getKey(),
                'processing_branch_id' => $branch->getKey(),
                'status' => 'in_progress',
            ],
            [[
                'lab_test_id' => $labTest->getKey(),
                'test_name_snapshot' => $labTest->name,
                'test_category_snapshot' => $labTest->category,
                'price_snapshot' => $labTest->price,
                'parameters_snapshot' => $labTest->parameters,
                'status' => 'in_progress',
            ]]
        );

        $this->withHeaders($context['headers'])
            ->postJson('/api/referrals', [
                'order_item_uuid' => (
                    $withoutSpecimen->items->firstOrFail()->uuid
                ),
                'referral_laboratory_test_uuid' => (
                    $directory['mapping_uuid']
                ),
                'specimen_uuids' => [(string) Str::uuid()],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('specimen_uuids');

        $pendingItemOrder = $this->createLaboratoryOrder(
            $context['tenant'],
            $patient,
            [
                'collection_branch_id' => $branch->getKey(),
                'processing_branch_id' => $branch->getKey(),
                'status' => 'in_progress',
            ],
            [[
                'lab_test_id' => $labTest->getKey(),
                'test_name_snapshot' => $labTest->name,
                'test_category_snapshot' => $labTest->category,
                'price_snapshot' => $labTest->price,
                'parameters_snapshot' => $labTest->parameters,
                'status' => 'pending',
            ]]
        );
        $pendingItem = $pendingItemOrder->items->firstOrFail();
        $pendingSpecimen = $this->createCollectedSpecimen(
            $context['tenant'],
            $pendingItemOrder,
            [$pendingItem->uuid],
            [
                'status' => 'received',
                'received_at' => now(),
            ]
        );

        $this->withHeaders($context['headers'])
            ->postJson('/api/referrals', [
                'order_item_uuid' => $pendingItem->uuid,
                'referral_laboratory_test_uuid' => (
                    $directory['mapping_uuid']
                ),
                'specimen_uuids' => [$pendingSpecimen->uuid],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_item_uuid');

        $this->assertSame(
            0,
            $this->tenantReferralCount(
                $context['tenant'],
                'laboratory_referrals'
            )
        );
    }

    public function test_creation_rejects_ineligible_item_mapping_and_duplicate_open_referral(): void
    {
        $context = $this->authenticatedReferralContext(
            'referral-workflow-eligibility'
        );
        $labTest = $this->createLabTest($context['tenant'], [
            'name' => 'Eligible Referral Test',
        ]);
        $otherTest = $this->createLabTest($context['tenant'], [
            'name' => 'Different Referral Test',
        ]);
        $directory = $this->createReferralDirectory(
            $context['headers'],
            $labTest,
            'ELIGIBLE'
        );
        $otherDirectory = $this->createReferralDirectory(
            $context['headers'],
            $otherTest,
            'OTHER'
        );
        $order = $this->createReferralReadyOrder(
            $context['tenant'],
            $context['headers'],
            $labTest
        );

        $this->createReferralDraft(
            $context['headers'],
            $context['tenant'],
            $order,
            $otherDirectory['mapping_uuid']
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'referral_laboratory_test_uuid'
            );

        $first = $this->createReferralDraft(
            $context['headers'],
            $context['tenant'],
            $order,
            $directory['mapping_uuid']
        )->assertCreated();

        $this->createReferralDraft(
            $context['headers'],
            $context['tenant'],
            $order,
            $directory['mapping_uuid']
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_item_uuid');

        $this->assertSame(
            1,
            $this->tenantReferralCount(
                $context['tenant'],
                'laboratory_referrals'
            )
        );
        $this->assertNotNull($first->json('data.uuid'));
    }

    public function test_dispatch_acknowledge_and_reject_write_one_event_each(): void
    {
        $context = $this->authenticatedReferralContext(
            'referral-workflow-transitions'
        );
        $labTest = $this->createLabTest($context['tenant'], [
            'name' => 'Workflow Referral Test',
        ]);
        $directory = $this->createReferralDirectory(
            $context['headers'],
            $labTest,
            'WORKFLOW'
        );
        $order = $this->createReferralReadyOrder(
            $context['tenant'],
            $context['headers'],
            $labTest
        );
        $referralUuid = $this->createReferralDraft(
            $context['headers'],
            $context['tenant'],
            $order,
            $directory['mapping_uuid']
        )->assertCreated()->json('data.uuid');

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/referrals/'.$referralUuid.'/dispatch',
                [
                    'quoted_cost' => '1400.00',
                    'external_reference' => 'LAB-TRACK-1',
                    'dispatch_notes' => 'Sent by approved courier.',
                ]
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'dispatched')
            ->assertJsonPath('data.quoted_cost', '1400.00')
            ->assertJsonPath(
                'data.timeline.1.event_type',
                'dispatched'
            );

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/referrals/'.$referralUuid.'/acknowledge',
                ['external_reference' => 'LAB-TRACK-ACK']
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'acknowledged')
            ->assertJsonPath(
                'data.timeline.2.event_type',
                'acknowledged'
            );

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/referrals/'.$referralUuid.'/reject',
                ['reason' => 'External analyser is unavailable.']
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath(
                'data.rejection_reason',
                'External analyser is unavailable.'
            )
            ->assertJsonPath(
                'data.timeline.3.event_type',
                'rejected'
            );

        $stored = $this->storedReferral(
            $context['tenant'],
            $referralUuid
        );

        $this->assertNull($stored->open_order_item_key);
        $this->assertCount(4, $stored->events);
    }

    public function test_cancel_requires_reason_releases_item_and_terminal_referral_cannot_transition(): void
    {
        $context = $this->authenticatedReferralContext(
            'referral-workflow-cancel'
        );
        $labTest = $this->createLabTest($context['tenant']);
        $directory = $this->createReferralDirectory(
            $context['headers'],
            $labTest,
            'CANCEL'
        );
        $order = $this->createReferralReadyOrder(
            $context['tenant'],
            $context['headers'],
            $labTest
        );
        $referralUuid = $this->createReferralDraft(
            $context['headers'],
            $context['tenant'],
            $order,
            $directory['mapping_uuid']
        )->assertCreated()->json('data.uuid');

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/referrals/'.$referralUuid.'/cancel',
                ['reason' => 'No']
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/referrals/'.$referralUuid.'/cancel',
                ['reason' => 'Clinician withdrew the send-out request.']
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/referrals/'.$referralUuid.'/dispatch',
                []
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $replacement = $this->createReferralDraft(
            $context['headers'],
            $context['tenant'],
            $order,
            $directory['mapping_uuid']
        )
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft');

        $this->assertNotSame(
            $referralUuid,
            $replacement->json('data.uuid')
        );
    }

    public function test_branch_scoped_users_cannot_read_or_mutate_other_branch_referrals(): void
    {
        $context = $this->authenticatedReferralContext(
            'referral-workflow-branch'
        );
        $headOffice = $this->headOffice($context['tenant']);
        $otherBranch = $this->runInTenant(
            $context['tenant'],
            static fn (): Branch => Branch::query()->create([
                'code' => 'LAB-OTHER',
                'name' => 'Other Laboratory Branch',
                'type' => 'laboratory',
                'country' => 'Nigeria',
                'is_head_office' => false,
                'is_active' => true,
            ])
        );
        $labTest = $this->createLabTest($context['tenant']);
        $directory = $this->createReferralDirectory(
            $context['headers'],
            $labTest,
            'BRANCH'
        );
        $order = $this->createReferralReadyOrder(
            $context['tenant'],
            $context['headers'],
            $labTest,
            $otherBranch
        );
        $referralUuid = $this->createReferralDraft(
            $context['headers'],
            $context['tenant'],
            $order,
            $directory['mapping_uuid']
        )->assertCreated()->json('data.uuid');
        $technicianHeaders = $this->headersForTenantUser(
            $context['tenant'],
            $context['registration']['domain'],
            'lab_technician',
            [$headOffice->getKey()]
        );

        $this->createReferralDraft(
            $technicianHeaders,
            $context['tenant'],
            $order,
            $directory['mapping_uuid']
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_item_uuid');

        $this->withHeaders($technicianHeaders)
            ->getJson('/api/referrals/'.$referralUuid)
            ->assertNotFound();

        $this->withHeaders($technicianHeaders)
            ->postJson(
                '/api/referrals/'.$referralUuid.'/dispatch',
                []
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_item_uuid');
    }

    public function test_disabled_entitlement_blocks_referral_creation_without_writes(): void
    {
        $context = $this->authenticatedReferralContext(
            'referral-workflow-entitlement'
        );
        $labTest = $this->createLabTest($context['tenant']);
        $directory = $this->createReferralDirectory(
            $context['headers'],
            $labTest,
            'ENTITLEMENT'
        );
        $order = $this->createReferralReadyOrder(
            $context['tenant'],
            $context['headers'],
            $labTest
        );
        $subscription = TenantSubscription::query()
            ->where('tenant_id', $context['tenant']->getKey())
            ->latest('starts_at')
            ->firstOrFail();
        $originalPlanId = $subscription->plan_id;
        $plan = Plan::query()->create([
            'code' => 'workflow-referrals-disabled-'
                .strtolower(Str::random(8)),
            'name' => 'Workflow Referrals Disabled',
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
            $this->createReferralDraft(
                $context['headers'],
                $context['tenant'],
                $order,
                $directory['mapping_uuid']
            )->assertForbidden();

            $this->assertSame(
                0,
                $this->tenantReferralCount(
                    $context['tenant'],
                    'laboratory_referrals'
                )
            );
            $this->assertSame(
                0,
                $this->tenantReferralCount(
                    $context['tenant'],
                    'laboratory_referral_events'
                )
            );
        } finally {
            $this->restoreLandlordContext();
            $subscription->refresh()->update([
                'plan_id' => $originalPlanId,
            ]);
            $plan->delete();
        }
    }

    public function test_referral_records_are_isolated_between_tenants(): void
    {
        $first = $this->authenticatedReferralContext(
            'referral-workflow-tenant-a'
        );
        $labTest = $this->createLabTest($first['tenant']);
        $directory = $this->createReferralDirectory(
            $first['headers'],
            $labTest,
            'TENANT-A'
        );
        $order = $this->createReferralReadyOrder(
            $first['tenant'],
            $first['headers'],
            $labTest
        );
        $referralUuid = $this->createReferralDraft(
            $first['headers'],
            $first['tenant'],
            $order,
            $directory['mapping_uuid']
        )->assertCreated()->json('data.uuid');

        $second = $this->authenticatedReferralContext(
            'referral-workflow-tenant-b'
        );

        $this->withHeaders($second['headers'])
            ->getJson('/api/referrals/'.$referralUuid)
            ->assertNotFound();

        $this->assertSame(
            0,
            $this->tenantReferralCount(
                $second['tenant'],
                'laboratory_referrals'
            )
        );
    }
}
