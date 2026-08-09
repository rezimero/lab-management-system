<?php

namespace Tests\Feature\Tenant\Referral;

use App\Models\Branch;
use App\Models\LaboratoryReferral;
use App\Models\LaboratoryReferralResult;
use App\Models\Plan;
use App\Models\TenantSubscription;
use App\Models\TestResult;
use App\Support\Commercial\CommercialFeatureCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Support\Tenant\ReferralManagementTestCase;

class TenantReferralExternalResultTest extends ReferralManagementTestCase
{
    use RefreshDatabase;

    public function test_capture_creates_external_version_and_standard_draft_result(): void
    {
        $setup = $this->referralSetup(
            'referral-result-capture'
        );

        $response = $this->withHeaders($setup['headers'])
            ->postJson(
                '/api/referrals/'
                .$setup['referral_uuid']
                .'/results',
                $this->externalResultPayload([
                    'external_report_number' => ' EXT-RPT-1001 ',
                    'final_cost' => '1500.00',
                    'notes' => ' Verified by the external laboratory. ',
                ])
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'result_received')
            ->assertJsonPath(
                'data.final_cost',
                '1500.00'
            )
            ->assertJsonPath(
                'data.latest_external_result.version',
                1
            )
            ->assertJsonPath(
                'data.latest_external_result.external_report_number',
                'EXT-RPT-1001'
            )
            ->assertJsonPath(
                'data.standard_result.status',
                'draft'
            )
            ->assertJsonPath(
                'data.external_results.0.version',
                1
            )
            ->assertJsonPath(
                'data.timeline.2.event_type',
                'external_result_received'
            );

        $resultUuid = $response->json(
            'data.standard_result.uuid'
        );

        $this->assertIsString($resultUuid);

        $this->withHeaders($setup['headers'])
            ->getJson('/api/test-results/'.$resultUuid)
            ->assertOk()
            ->assertJsonPath(
                'data.referral_source.referral_uuid',
                $setup['referral_uuid']
            )
            ->assertJsonPath(
                'data.referral_source.external_result_version',
                1
            )
            ->assertJsonPath(
                'data.referral_source.external_report_number',
                'EXT-RPT-1001'
            );

        $stored = $this->runInTenant(
            $setup['tenant'],
            fn (): LaboratoryReferral => LaboratoryReferral::query()
                ->with([
                    'externalResults.receiver',
                    'externalResults.testResult',
                    'orderItem.testResult',
                    'events',
                ])
                ->where('uuid', $setup['referral_uuid'])
                ->firstOrFail()
        );

        $external = $stored->externalResults->firstOrFail();

        $this->assertSame(1, $external->version);
        $this->assertNull($external->supersedes_referral_result_id);
        $this->assertSame(
            $stored->orderItem->testResult->getKey(),
            $external->test_result_id
        );
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $external->source_payload_hash
        );
        $this->assertCount(3, $stored->events);
    }

    public function test_unknown_parameters_and_future_report_time_are_rejected_without_writes(): void
    {
        $setup = $this->referralSetup(
            'referral-result-validation'
        );

        $payload = $this->externalResultPayload();
        $payload['results_data'][0]['name'] = 'Unknown Analyte';

        $this->withHeaders($setup['headers'])
            ->postJson(
                '/api/referrals/'
                .$setup['referral_uuid']
                .'/results',
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'results_data.0.name'
            );

        $this->withHeaders($setup['headers'])
            ->postJson(
                '/api/referrals/'
                .$setup['referral_uuid']
                .'/results',
                $this->externalResultPayload([
                    'reported_at' => now()
                        ->addDays(2)
                        ->toISOString(),
                ])
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reported_at');

        $this->assertSame(
            0,
            $this->tenantReferralCount(
                $setup['tenant'],
                'laboratory_referral_results'
            )
        );
        $this->assertSame(
            0,
            $this->tenantReferralCount(
                $setup['tenant'],
                'test_results'
            )
        );

        $this->assertSame(
            'dispatched',
            $this->storedReferral(
                $setup['tenant'],
                $setup['referral_uuid']
            )->status
        );
    }

    public function test_normal_result_entry_is_blocked_until_referral_is_terminal(): void
    {
        $setup = $this->referralSetup(
            'referral-result-local-guard',
            false
        );
        $item = $setup['order']->items->firstOrFail();

        $this->createDraftResult(
            $setup['headers'],
            $item
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_item');

        $this->withHeaders($setup['headers'])
            ->postJson(
                '/api/referrals/'
                .$setup['referral_uuid']
                .'/cancel',
                [
                    'reason' => (
                        'The clinician requested local processing instead.'
                    ),
                ]
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->createDraftResult(
            $setup['headers'],
            $item
        )
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_versions_are_linear_and_follow_standard_result_state(): void
    {
        $setup = $this->referralSetup(
            'referral-result-versioning'
        );

        $first = $this->captureExternalResult(
            $setup,
            $this->externalResultPayload([
                'external_report_number' => 'EXT-V1',
            ])
        )->assertOk();

        $resultUuid = $first->json(
            'data.standard_result.uuid'
        );

        $secondPayload = $this->externalResultPayload([
            'external_report_number' => 'EXT-V2',
        ]);
        $secondPayload['results_data'][0]['value'] = '14.1';

        $second = $this->captureExternalResult(
            $setup,
            $secondPayload
        )
            ->assertOk()
            ->assertJsonPath(
                'data.latest_external_result.version',
                2
            )
            ->assertJsonPath(
                'data.standard_result.uuid',
                $resultUuid
            );

        $this->assertIsString(
            $second->json(
                'data.external_results.1.supersedes_uuid'
            )
        );

        $this->withHeaders($setup['headers'])
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/submit'
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'submitted');

        $this->captureExternalResult(
            $setup,
            $this->externalResultPayload([
                'external_report_number' => 'EXT-BLOCKED',
            ])
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('test_result');

        $this->withHeaders($setup['headers'])
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/reject',
                [
                    'rejection_reason' => (
                        'External laboratory issued a correction.'
                    ),
                ]
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected');

        $this->withHeaders($setup['headers'])
            ->getJson(
                '/api/referrals/'.$setup['referral_uuid']
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'result_received');

        $thirdPayload = $this->externalResultPayload([
            'external_report_number' => 'EXT-V3',
        ]);
        $thirdPayload['results_data'][0]['value'] = '14.3';

        $this->captureExternalResult(
            $setup,
            $thirdPayload
        )
            ->assertOk()
            ->assertJsonPath(
                'data.latest_external_result.version',
                3
            )
            ->assertJsonPath(
                'data.standard_result.status',
                'draft'
            )
            ->assertJsonCount(3, 'data.external_results');

        $versions = $this->runInTenant(
            $setup['tenant'],
            fn () => LaboratoryReferralResult::query()
                ->whereHas(
                    'referral',
                    fn ($query) => $query->where(
                        'uuid',
                        $setup['referral_uuid']
                    )
                )
                ->orderBy('version')
                ->get()
        );

        $this->assertSame([1, 2, 3], $versions->pluck('version')->all());
        $this->assertSame(
            $versions->get(0)?->getKey(),
            $versions->get(1)?->supersedes_referral_result_id
        );
        $this->assertSame(
            $versions->get(1)?->getKey(),
            $versions->get(2)?->supersedes_referral_result_id
        );
    }

    public function test_approval_completes_item_and_referral_atomically(): void
    {
        $setup = $this->referralSetup(
            'referral-result-completion'
        );

        $capture = $this->captureExternalResult(
            $setup,
            $this->externalResultPayload([
                'external_report_number' => 'EXT-COMPLETE',
                'final_cost' => '1750.00',
            ])
        )->assertOk();

        $resultUuid = $capture->json(
            'data.standard_result.uuid'
        );

        $this->withHeaders($setup['headers'])
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/submit'
            )
            ->assertOk();

        $this->withHeaders($setup['headers'])
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/approve'
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath(
                'data.order_item.status',
                'completed'
            )
            ->assertJsonPath(
                'data.referral_source.referral_status',
                'completed'
            );

        $this->withHeaders($setup['headers'])
            ->getJson(
                '/api/referrals/'.$setup['referral_uuid']
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath(
                'data.standard_result.status',
                'approved'
            )
            ->assertJsonPath(
                'data.timeline.3.event_type',
                'completed'
            );

        $stored = $this->storedReferral(
            $setup['tenant'],
            $setup['referral_uuid']
        );

        $this->assertNull($stored->open_order_item_key);
        $this->assertSame('completed', $stored->status);
        $this->assertNotNull($stored->completed_at);

        $item = $this->storedOrderItem(
            $setup['tenant'],
            $setup['order']->items->firstOrFail()->uuid
        );

        $this->assertSame('completed', $item->status);
        $this->assertSame(
            TestResult::STATUS_APPROVED,
            $item->testResult?->status
        );

        $this->captureExternalResult(
            $setup,
            $this->externalResultPayload()
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_capture_requires_permission_and_active_entitlement(): void
    {
        $setup = $this->referralSetup(
            'referral-result-access'
        );
        $viewerHeaders = $this->headersForTenantUser(
            $setup['tenant'],
            $setup['domain'],
            'receptionist'
        );

        $this->withHeaders($viewerHeaders)
            ->postJson(
                '/api/referrals/'
                .$setup['referral_uuid']
                .'/results',
                $this->externalResultPayload()
            )
            ->assertForbidden();

        $subscription = TenantSubscription::query()
            ->where('tenant_id', $setup['tenant']->getKey())
            ->latest('starts_at')
            ->firstOrFail();
        $originalPlanId = $subscription->plan_id;
        $plan = Plan::query()->create([
            'code' => 'external-results-disabled-'
                .strtolower(Str::random(8)),
            'name' => 'External Results Disabled',
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
            $this->captureExternalResult(
                $setup,
                $this->externalResultPayload()
            )->assertForbidden();

            $this->assertSame(
                0,
                $this->tenantReferralCount(
                    $setup['tenant'],
                    'laboratory_referral_results'
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

    public function test_capture_requires_processing_branch_access(): void
    {
        $context = $this->authenticatedReferralContext(
            'referral-result-branch'
        );
        $headOffice = $this->headOffice($context['tenant']);
        $otherBranch = $this->runInTenant(
            $context['tenant'],
            static fn (): Branch => Branch::query()->create([
                'code' => 'RESULT-OTHER',
                'name' => 'Result Other Branch',
                'type' => 'laboratory',
                'country' => 'Nigeria',
                'is_head_office' => false,
                'is_active' => true,
            ])
        );
        $labTest = $this->createLabTest($context['tenant'], [
            'name' => 'Referral Branch Result Test',
            'parameters' => $this->defaultResultParameters(),
        ]);
        $directory = $this->createReferralDirectory(
            $context['headers'],
            $labTest,
            'RESULT-BRANCH'
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

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/referrals/'.$referralUuid.'/dispatch'
            )
            ->assertOk();

        $technicianHeaders = $this->headersForTenantUser(
            $context['tenant'],
            $context['registration']['domain'],
            'lab_technician',
            [$headOffice->getKey()]
        );

        $this->withHeaders($technicianHeaders)
            ->postJson(
                '/api/referrals/'.$referralUuid.'/results',
                $this->externalResultPayload()
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_item_uuid');

        $this->assertSame(
            0,
            $this->tenantReferralCount(
                $context['tenant'],
                'laboratory_referral_results'
            )
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function referralSetup(
        string $prefix,
        bool $dispatch = true
    ): array {
        $context = $this->authenticatedReferralContext($prefix);
        $labTest = $this->createLabTest($context['tenant'], [
            'name' => 'External Referral Result Test',
            'category' => 'Haematology',
            'price' => '5000.00',
            'parameters' => $this->defaultResultParameters(),
        ]);
        $directory = $this->createReferralDirectory(
            $context['headers'],
            $labTest,
            strtoupper(substr(hash('sha256', $prefix), 0, 8))
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

        if ($dispatch) {
            $this->withHeaders($context['headers'])
                ->postJson(
                    '/api/referrals/'.$referralUuid.'/dispatch',
                    [
                        'external_reference' => 'TRACK-'.$prefix,
                    ]
                )
                ->assertOk()
                ->assertJsonPath('data.status', 'dispatched');
        }

        return [
            'tenant' => $context['tenant'],
            'headers' => $context['headers'],
            'domain' => $context['registration']['domain'],
            'order' => $order,
            'referral_uuid' => $referralUuid,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function externalResultPayload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'external_report_number' => 'EXT-RPT-BASE',
                'results_data' => $this->completeResultPayload()[
                    'results_data'
                ],
                'notes' => 'External laboratory result received.',
                'reported_at' => now()->subHour()->toISOString(),
                'final_cost' => '1250.00',
            ],
            $overrides
        );
    }

    private function captureExternalResult(
        array $setup,
        array $payload
    ): TestResponse {
        return $this->withHeaders($setup['headers'])
            ->postJson(
                '/api/referrals/'
                .$setup['referral_uuid']
                .'/results',
                $payload
            );
    }
}
