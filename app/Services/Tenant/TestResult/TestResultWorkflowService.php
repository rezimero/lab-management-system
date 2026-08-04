<?php

namespace App\Services\Tenant\TestResult;

use App\Models\LaboratoryOrderItem;
use App\Models\LaboratoryReferral;
use App\Models\LaboratoryReferralEvent;
use App\Models\LaboratoryReferralResult;
use App\Models\Tenant\User as TenantUser;
use App\Models\TestResult;
use App\Services\Tenant\Referral\ReferralActorSnapshotFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TestResultWorkflowService
{
    /**
     * Relations required by result responses.
     *
     * @var array<int, string>
     */
    private const RELATIONS = [
        'orderItem.order.patient',
        'enteredBy',
        'submittedBy',
        'reviewedBy',
        'latestAmendment',
        'originatingReferralResult.referral',
    ];

    public function __construct(
        private readonly TestResultSubmissionValidator $submissionValidator,
        private readonly ReferralActorSnapshotFactory $actorSnapshots
    ) {}

    /**
     * Submit a complete draft for review.
     */
    public function submit(
        string $uuid,
        int $userId
    ): TestResult {
        return DB::connection('tenant')->transaction(
            function () use ($uuid, $userId): TestResult {
                $result = $this->lockResult($uuid);
                $item = $this->lockOrderItem($result);

                $this->assertStatus(
                    $result,
                    TestResult::STATUS_DRAFT,
                    'Only a draft test result can be submitted.'
                );

                $this->assertProcessingIsActive($item);

                $this->submissionValidator->validate(
                    $result,
                    $item
                );

                $result->forceFill([
                    'status' => TestResult::STATUS_SUBMITTED,
                    'submitted_by' => $userId,
                    'submitted_at' => now(),
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'rejection_reason' => null,
                ])->save();

                return $this->refreshResult($result);
            },
            3
        );
    }

    /**
     * Approve a submitted result.
     */
    public function approve(
        string $uuid,
        int $userId
    ): TestResult {
        return DB::connection('tenant')->transaction(
            function () use ($uuid, $userId): TestResult {
                $result = $this->lockResult($uuid);
                $item = $this->lockOrderItem($result);

                $this->assertStatus(
                    $result,
                    TestResult::STATUS_SUBMITTED,
                    'Only a submitted test result can be approved.'
                );

                $this->assertProcessingIsActive($item);

                $reviewedAt = now();

                $result->forceFill([
                    'status' => TestResult::STATUS_APPROVED,
                    'reviewed_by' => $userId,
                    'reviewed_at' => $reviewedAt,
                    'rejection_reason' => null,
                ])->save();

                $item->forceFill([
                    'status' => 'completed',
                    'completed_at' => $reviewedAt,
                ])->save();

                $this->completeOriginatingReferral(
                    $result,
                    $userId,
                    $reviewedAt
                );

                return $this->refreshResult($result);
            },
            3
        );
    }

    /**
     * Reject a submitted result for correction.
     */
    public function reject(
        string $uuid,
        string $reason,
        int $userId
    ): TestResult {
        return DB::connection('tenant')->transaction(
            function () use (
                $uuid,
                $reason,
                $userId
            ): TestResult {
                $result = $this->lockResult($uuid);
                $item = $this->lockOrderItem($result);

                $this->assertStatus(
                    $result,
                    TestResult::STATUS_SUBMITTED,
                    'Only a submitted test result can be rejected.'
                );

                $this->assertProcessingIsActive($item);

                $result->forceFill([
                    'status' => TestResult::STATUS_REJECTED,
                    'reviewed_by' => $userId,
                    'reviewed_at' => now(),
                    'rejection_reason' => trim($reason),
                ])->save();

                return $this->refreshResult($result);
            },
            3
        );
    }

    /**
     * Complete the referral that originated the approved result.
     */
    private function completeOriginatingReferral(
        TestResult $result,
        int $userId,
        mixed $completedAt
    ): void {
        $externalResult = LaboratoryReferralResult::query()
            ->where('test_result_id', $result->getKey())
            ->orderByDesc('version')
            ->lockForUpdate()
            ->first();

        if ($externalResult === null) {
            return;
        }

        $referral = LaboratoryReferral::query()
            ->whereKey($externalResult->laboratory_referral_id)
            ->lockForUpdate()
            ->firstOrFail();

        if (
            $referral->status
                !== LaboratoryReferral::STATUS_RESULT_RECEIVED
        ) {
            throw ValidationException::withMessages([
                'test_result' => [
                    sprintf(
                        'The originating referral is %s and cannot be completed.',
                        $referral->status
                    ),
                ],
            ]);
        }

        $actor = TenantUser::query()
            ->whereKey($userId)
            ->firstOrFail();

        $referral->forceFill([
            'status' => LaboratoryReferral::STATUS_COMPLETED,
            'open_order_item_key' => null,
            'completed_by' => $userId,
            'completed_at' => $completedAt,
        ])->save();

        LaboratoryReferralEvent::query()->create([
            'laboratory_referral_id' => $referral->getKey(),
            'event_type' => 'completed',
            'from_status' => LaboratoryReferral::STATUS_RESULT_RECEIVED,
            'to_status' => LaboratoryReferral::STATUS_COMPLETED,
            'actor_id' => $userId,
            'actor_snapshot' => $this->actorSnapshots->make($actor),
            'payload' => [
                'test_result_uuid' => $result->uuid,
                'external_result_uuid' => $externalResult->uuid,
                'external_result_version' => (
                    (int) $externalResult->version
                ),
            ],
            'occurred_at' => $completedAt,
            'created_at' => $completedAt,
        ]);
    }

    /**
     * Lock and return a test result.
     */
    private function lockResult(
        string $uuid
    ): TestResult {
        return TestResult::query()
            ->where('uuid', $uuid)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Lock the result's order item.
     */
    private function lockOrderItem(
        TestResult $result
    ): LaboratoryOrderItem {
        if ($result->laboratory_order_item_id === null) {
            throw ValidationException::withMessages([
                'test_result' => [
                    'This legacy result is not connected to a laboratory order item.',
                ],
            ]);
        }

        return LaboratoryOrderItem::query()
            ->with('order')
            ->whereKey(
                $result->laboratory_order_item_id
            )
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Require the expected result status.
     */
    private function assertStatus(
        TestResult $result,
        string $expectedStatus,
        string $message
    ): void {
        if ($result->status === $expectedStatus) {
            return;
        }

        throw ValidationException::withMessages([
            'test_result' => [
                $message,
            ],
        ]);
    }

    /**
     * Require an active processing workflow.
     */
    private function assertProcessingIsActive(
        LaboratoryOrderItem $item
    ): void {
        if ($item->order?->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'test_result' => [
                    'The laboratory order is not in progress.',
                ],
            ]);
        }

        if ($item->status === 'in_progress') {
            return;
        }

        throw ValidationException::withMessages([
            'test_result' => [
                'The laboratory order item is not in progress.',
            ],
        ]);
    }

    /**
     * Reload the result and response relations.
     */
    private function refreshResult(
        TestResult $result
    ): TestResult {
        $result->refresh();

        return $result->load(self::RELATIONS);
    }
}
