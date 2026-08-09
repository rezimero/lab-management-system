<?php

namespace Tests\Feature\Tenant\Referral;

use App\Models\Branch;
use App\Models\LaboratoryReferral;
use App\Models\LaboratoryReferralEvent;
use App\Models\LaboratoryReferralResult;
use App\Models\ReferralLaboratory;
use App\Models\ReferralLaboratoryTest;
use App\Models\Tenant\User as TenantUser;
use App\Models\TestResult;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\Support\Tenant\TestResultManagementTestCase;

class TenantReferralFoundationTest extends TestResultManagementTestCase
{
    public function test_new_tenant_has_the_complete_referral_foundation_schema(): void
    {
        $context = $this->authenticatedTestResultContext(
            'referral-foundation-schema'
        );

        $this->runInTenant($context['tenant'], function (): void {
            foreach ([
                'referral_laboratories',
                'referral_laboratory_tests',
                'laboratory_referrals',
                'laboratory_referral_specimens',
                'laboratory_referral_events',
                'laboratory_referral_results',
            ] as $table) {
                $this->assertTrue(
                    Schema::connection('tenant')->hasTable($table),
                    'Missing referral table: '.$table
                );
            }

            $this->assertTrue(Schema::connection('tenant')->hasColumn(
                'laboratory_referrals',
                'open_order_item_key'
            ));
            $this->assertTrue(Schema::connection('tenant')->hasColumn(
                'laboratory_referrals',
                'laboratory_snapshot'
            ));
            $this->assertTrue(Schema::connection('tenant')->hasColumn(
                'laboratory_referral_results',
                'source_payload_hash'
            ));
        });
    }

    public function test_referral_models_preserve_item_ownership_and_immutable_evidence(): void
    {
        $context = $this->authenticatedTestResultContext(
            'referral-foundation-models'
        );
        $order = $this->createProcessingOrder(
            $context['tenant'],
            $context['headers']
        );
        $item = $order->items->firstOrFail();

        $this->startOrderItem($context['headers'], $item)
            ->assertOk();

        $this->runInTenant($context['tenant'], function () use ($item): void {
            $item->refresh();
            $user = TenantUser::query()->firstOrFail();
            $branch = Branch::query()
                ->where('is_head_office', true)
                ->firstOrFail();
            $item->order()->update([
                'collection_branch_id' => $branch->getKey(),
                'processing_branch_id' => $branch->getKey(),
            ]);
            $specimen = $item->specimens()->firstOrFail();

            $laboratory = ReferralLaboratory::query()->create([
                'code' => ' ext-lab ',
                'name' => 'External Reference Laboratory',
                'currency' => 'ngn',
                'is_active' => true,
                'created_by' => $user->getKey(),
                'updated_by' => $user->getKey(),
            ]);

            $mapping = ReferralLaboratoryTest::query()->create([
                'referral_laboratory_id' => $laboratory->getKey(),
                'lab_test_id' => $item->lab_test_id,
                'external_test_code' => ' ext-test ',
                'external_test_name' => $item->test_name_snapshot,
                'cost' => '1000.00',
                'currency' => 'ngn',
                'turnaround_hours' => 24,
                'is_active' => true,
                'created_by' => $user->getKey(),
                'updated_by' => $user->getKey(),
            ]);

            $referral = LaboratoryReferral::query()->create([
                'referral_number' => 'REF-20260804-FOUND001',
                'laboratory_order_item_id' => $item->getKey(),
                'referral_laboratory_id' => $laboratory->getKey(),
                'referral_laboratory_test_id' => $mapping->getKey(),
                'dispatch_branch_id' => $branch->getKey(),
                'open_order_item_key' => $item->uuid,
                'status' => LaboratoryReferral::STATUS_DRAFT,
                'laboratory_snapshot' => [
                    'code' => $laboratory->code,
                    'name' => $laboratory->name,
                ],
                'test_snapshot' => [
                    'external_test_code' => $mapping->external_test_code,
                    'external_test_name' => $mapping->external_test_name,
                ],
                'quoted_cost' => '1000.00',
                'currency' => 'ngn',
                'created_by' => $user->getKey(),
            ]);

            $referral->specimens()->attach($specimen->getKey(), [
                'specimen_snapshot' => json_encode([
                    'accession_number' => $specimen->accession_number,
                    'specimen_type' => $specimen->specimen_type,
                ], JSON_THROW_ON_ERROR),
                'linked_by' => $user->getKey(),
            ]);

            $event = LaboratoryReferralEvent::query()->create([
                'laboratory_referral_id' => $referral->getKey(),
                'event_type' => 'created',
                'from_status' => null,
                'to_status' => LaboratoryReferral::STATUS_DRAFT,
                'actor_id' => $user->getKey(),
                'actor_snapshot' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'payload' => [],
                'occurred_at' => now(),
            ]);

            $standardResult = TestResult::query()->create([
                'laboratory_order_item_id' => $item->getKey(),
                'results_data' => [[
                    'name' => 'Haemoglobin',
                    'value' => '13.5',
                ]],
                'notes' => 'Imported external result.',
                'status' => TestResult::STATUS_DRAFT,
                'entered_by' => $user->getKey(),
            ]);

            $externalResult = LaboratoryReferralResult::query()->create([
                'laboratory_referral_id' => $referral->getKey(),
                'version' => 1,
                'external_report_number' => 'EXT-RPT-001',
                'results_data' => $standardResult->results_data,
                'received_at' => now(),
                'received_by' => $user->getKey(),
                'receiver_snapshot' => [
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'source_payload_hash' => str_repeat('a', 64),
                'test_result_id' => $standardResult->getKey(),
            ]);

            $this->assertSame('EXT-LAB', $laboratory->code);
            $this->assertSame('NGN', $laboratory->currency);
            $this->assertSame('EXT-TEST', $mapping->external_test_code);
            $this->assertSame($referral->getKey(), $item->laboratoryReferrals()->firstOrFail()->getKey());
            $this->assertSame($referral->getKey(), $item->currentOpenReferral()->firstOrFail()->getKey());
            $this->assertSame($externalResult->getKey(), $standardResult->originatingReferralResult()->firstOrFail()->getKey());

            try {
                $event->update(['payload' => ['illegal' => true]]);
                $this->fail('Referral events must be immutable.');
            } catch (LogicException) {
                $this->assertTrue(true);
            }

            try {
                $externalResult->delete();
                $this->fail('Referral results must not be deleted.');
            } catch (LogicException) {
                $this->assertTrue(true);
            }
        });
    }
}
