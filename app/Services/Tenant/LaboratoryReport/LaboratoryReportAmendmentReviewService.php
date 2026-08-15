<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Events\Tenant\LaboratoryReport\LaboratoryReportAmendmentApproved;
use App\Events\Tenant\LaboratoryReport\LaboratoryReportAmendmentRejected;
use App\Events\Tenant\LaboratoryReport\LaboratoryReportSuperseded;
use App\Models\LaboratoryOrder;
use App\Models\LaboratoryReport;
use App\Models\LaboratoryReportAmendment;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Models\TestResult;
use App\Models\TestResultAmendment;
use App\Services\Tenant\Notification\CriticalResultEscalationService;
use App\Services\Tenant\Portal\PortalReportAvailabilityService;
use App\Services\Tenant\TestResult\TestResultEffectiveValueService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Throwable;

class LaboratoryReportAmendmentReviewService
{
    public function __construct(
        private readonly LaboratoryReportAmendmentActorSnapshotFactory $actorSnapshotFactory,
        private readonly TestResultEffectiveValueService $effectiveValueService,
        private readonly LaboratoryReportMediaSnapshotService $mediaSnapshotService,
        private readonly PortalReportAvailabilityService $portalAvailability,
        private readonly CriticalResultEscalationService $criticalEscalations
    ) {}

    /**
     * Approve one submitted amendment and issue a new immutable report.
     */
    public function approve(
        string $amendmentUuid,
        TenantUser $reviewer
    ): LaboratoryReportAmendment {
        $this->assertReviewerPermission($reviewer);

        $tenant = $this->currentTenant();
        $tenantId = (int) $tenant->getKey();
        $reviewerSnapshot = $this
            ->actorSnapshotFactory
            ->make($reviewer);

        $approved = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $targetReportUuid = (string) Str::uuid();

            try {
                $approved = DB::connection('tenant')->transaction(
                    function () use (
                        $amendmentUuid,
                        $reviewer,
                        $reviewerSnapshot,
                        $targetReportUuid,
                        $tenantId
                    ): LaboratoryReportAmendment {
                        return $this->approveLocked(
                            $amendmentUuid,
                            $reviewer,
                            $reviewerSnapshot,
                            $targetReportUuid,
                            $tenantId
                        );
                    },
                    1
                );

                break;
            } catch (Throwable $exception) {
                $this->mediaSnapshotService
                    ->deleteReportMedia(
                        $targetReportUuid
                    );

                if (
                    $attempt < 3
                    && $this->isConcurrencyFailure(
                        $exception
                    )
                ) {
                    usleep(100000 * $attempt);

                    continue;
                }

                throw $exception;
            }
        }

        if (! $approved instanceof LaboratoryReportAmendment) {
            throw new LogicException(
                'The amendment approval did not produce a result.'
            );
        }

        $approved->load([
            'sourceReport',
            'targetReport',
            'requestedBy',
            'reviewedBy',
            'resultAmendments.testResult',
        ]);

        $sourceReport = $approved->sourceReport;
        $targetReport = $approved->targetReport;

        if (
            ! $sourceReport instanceof LaboratoryReport
            || ! $targetReport instanceof LaboratoryReport
        ) {
            throw new LogicException(
                'Approved amendment report lineage is incomplete.'
            );
        }

        LaboratoryReportAmendmentApproved::dispatch(
            $tenant->id,
            $approved->uuid,
            $sourceReport->uuid,
            $targetReport->uuid,
            $reviewerSnapshot
        );

        LaboratoryReportSuperseded::dispatch(
            $tenant->id,
            $sourceReport->uuid,
            $targetReport->uuid
        );

        return $approved;
    }

    /**
     * Reject one submitted amendment as immutable audit evidence.
     */
    public function reject(
        string $amendmentUuid,
        string $reason,
        TenantUser $reviewer
    ): LaboratoryReportAmendment {
        $this->assertReviewerPermission($reviewer);

        $tenant = $this->currentTenant();
        $reviewerSnapshot = $this
            ->actorSnapshotFactory
            ->make($reviewer);
        $reason = trim($reason);

        if (
            mb_strlen($reason) < 10
            || preg_match(
                '/[\pL\pN]{3,}/u',
                $reason
            ) !== 1
        ) {
            throw ValidationException::withMessages([
                'rejection_reason' => [
                    'Provide a meaningful rejection reason.',
                ],
            ]);
        }

        $amendment = DB::connection('tenant')->transaction(
            function () use (
                $amendmentUuid,
                $reason,
                $reviewer,
                $reviewerSnapshot
            ): LaboratoryReportAmendment {
                $amendment = LaboratoryReportAmendment::query()
                    ->where('uuid', $amendmentUuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertSubmitted($amendment);

                $amendment->forceFill([
                    'status' => (
                        LaboratoryReportAmendment::STATUS_REJECTED
                    ),
                    'open_source_report_id' => null,
                    'target_report_id' => null,
                    'reviewed_by' => $reviewer->id,
                    'reviewer_snapshot' => $reviewerSnapshot,
                    'reviewed_at' => now(),
                    'rejection_reason' => $reason,
                ])->save();

                return $amendment->fresh();
            },
            3
        );

        $amendment->load([
            'sourceReport',
            'targetReport',
            'requestedBy',
            'reviewedBy',
            'resultAmendments.testResult',
        ]);

        LaboratoryReportAmendmentRejected::dispatch(
            $tenant->id,
            $amendment->uuid,
            $amendment->sourceReport->uuid,
            $reviewerSnapshot
        );

        return $amendment;
    }

    /**
     * @param  array<string, mixed>  $reviewerSnapshot
     */
    private function approveLocked(
        string $amendmentUuid,
        TenantUser $reviewer,
        array $reviewerSnapshot,
        string $targetReportUuid,
        int $tenantId
    ): LaboratoryReportAmendment {
        $amendment = LaboratoryReportAmendment::query()
            ->where('uuid', $amendmentUuid)
            ->lockForUpdate()
            ->firstOrFail();

        $this->assertSubmitted($amendment);

        if ($amendment->requested_by === $reviewer->id) {
            throw ValidationException::withMessages([
                'authorization' => [
                    'A requester cannot approve their own amendment.',
                ],
            ]);
        }

        $sourceReport = LaboratoryReport::query()
            ->whereKey($amendment->source_report_id)
            ->lockForUpdate()
            ->firstOrFail();

        $order = LaboratoryOrder::query()
            ->whereKey(
                $sourceReport->laboratory_order_id
            )
            ->lockForUpdate()
            ->firstOrFail();

        if ($order->status !== 'completed') {
            throw ValidationException::withMessages([
                'laboratory_order' => [
                    'The source laboratory order must remain completed.',
                ],
            ]);
        }

        $latestReport = LaboratoryReport::query()
            ->where(
                'lineage_uuid',
                $sourceReport->lineage_uuid
            )
            ->orderByDesc('version')
            ->lockForUpdate()
            ->firstOrFail();

        if ($latestReport->id !== $sourceReport->id) {
            throw ValidationException::withMessages([
                'laboratory_report' => [
                    'The source report has already been superseded.',
                ],
            ]);
        }

        $patientSnapshot = $this->snapshotArray(
            $amendment->proposed_patient_snapshot,
            'patient'
        );
        $orderSnapshot = $this->snapshotArray(
            $amendment->proposed_order_snapshot,
            'order'
        );
        $resultsSnapshot = $this->snapshotArray(
            $amendment->proposed_results_snapshot,
            'results'
        );
        $changeSummary = $this->snapshotArray(
            $amendment->change_summary,
            'change summary'
        );

        $resultChanges = $changeSummary['results'] ?? [];

        if (! is_array($resultChanges)) {
            throw ValidationException::withMessages([
                'amendment' => [
                    'The stored result change summary is invalid.',
                ],
            ]);
        }

        usort(
            $resultChanges,
            static fn (array $first, array $second): int => (
                strcmp(
                    (string) ($first['result_uuid'] ?? ''),
                    (string) ($second['result_uuid'] ?? '')
                )
            )
        );

        $targetItems = $resultsSnapshot['items'] ?? null;

        if (! is_array($targetItems)) {
            throw ValidationException::withMessages([
                'amendment' => [
                    'The proposed result snapshot is invalid.',
                ],
            ]);
        }

        $targetItemIndexes = [];

        foreach ($targetItems as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $resultUuid = $item['result_uuid'] ?? null;

            if (is_string($resultUuid) && $resultUuid !== '') {
                $targetItemIndexes[$resultUuid] = $index;
            }
        }

        $approvedAt = now();

        foreach ($resultChanges as $change) {
            $resultUuid = $change['result_uuid'] ?? null;

            if (
                ! is_string($resultUuid)
                || ! array_key_exists(
                    $resultUuid,
                    $targetItemIndexes
                )
            ) {
                throw ValidationException::withMessages([
                    'amendment' => [
                        'A corrected result is missing from the proposed snapshot.',
                    ],
                ]);
            }

            $result = TestResult::query()
                ->with('orderItem')
                ->where('uuid', $resultUuid)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $result->status !== TestResult::STATUS_APPROVED
                || $result->orderItem === null
                || $result->orderItem->laboratory_order_id
                    !== $sourceReport->laboratory_order_id
                || $result->orderItem->uuid
                    !== ($change['order_item_uuid'] ?? null)
            ) {
                throw ValidationException::withMessages([
                    'amendment' => [
                        'A corrected result no longer belongs to the source report.',
                    ],
                ]);
            }

            $effective = $this->effectiveValueService
                ->resolve($result, true);

            if (
                $effective['effective_version']
                    !== (int) (
                        $change['source_effective_version']
                        ?? 0
                    )
                || ! hash_equals(
                    (string) (
                        $change['source_payload_hash']
                        ?? ''
                    ),
                    $effective['source_payload_hash']
                )
            ) {
                throw ValidationException::withMessages([
                    'amendment' => [
                        'An effective source result changed after submission.',
                    ],
                ]);
            }

            $targetIndex = $targetItemIndexes[$resultUuid];
            $targetItem = $targetItems[$targetIndex];

            if (! is_array($targetItem)) {
                throw ValidationException::withMessages([
                    'amendment' => [
                        'A proposed result item is invalid.',
                    ],
                ]);
            }

            $targetVersion = (
                $effective['effective_version'] + 1
            );

            if (
                $targetVersion
                !== (int) (
                    $change['target_effective_version']
                    ?? 0
                )
                || $targetVersion
                    !== (int) (
                        $targetItem['effective_version']
                        ?? 0
                    )
            ) {
                throw ValidationException::withMessages([
                    'amendment' => [
                        'The proposed result version is not sequential.',
                    ],
                ]);
            }

            $resultAmendmentUuid = (string) Str::uuid();

            $resultAmendment = TestResultAmendment::query()->create([
                'uuid' => $resultAmendmentUuid,
                'laboratory_report_amendment_id' => (
                    $amendment->id
                ),
                'test_result_id' => $result->id,
                'version' => $targetVersion,
                'supersedes_test_result_amendment_id' => (
                    $effective['latest_amendment_id']
                ),
                'source_payload_hash' => (
                    $effective['source_payload_hash']
                ),
                'corrected_results_data' => (
                    $targetItem['results_data']
                    ?? []
                ),
                'corrected_notes' => (
                    $targetItem['notes']
                    ?? null
                ),
                'reason' => $amendment->reason,
                'amended_by' => $amendment->requested_by,
                'amendment_actor_snapshot' => (
                    $amendment->requester_snapshot
                ),
                'approved_by' => $reviewer->id,
                'approval_actor_snapshot' => (
                    $reviewerSnapshot
                ),
                'approved_at' => $approvedAt,
                'created_at' => $approvedAt,
            ]);

            $criticalCount = collect(
                $resultAmendment->corrected_results_data ?? []
            )
                ->filter(
                    static fn (mixed $row): bool => (
                        is_array($row)
                        && strtolower((string) ($row['flag'] ?? ''))
                            === 'critical'
                    )
                )
                ->count();

            if ($criticalCount > 0) {
                $this->criticalEscalations->recordApproved(
                    $result->uuid,
                    $criticalCount,
                    'test-result-amendment:'
                        .$resultAmendment->uuid
                );
            }

            $targetItem['result_amendment_uuid'] = (
                $resultAmendmentUuid
            );
            $targetItems[$targetIndex] = $targetItem;
        }

        $resultsSnapshot['items'] = array_values(
            $targetItems
        );

        $mediaSnapshot = $this->mediaSnapshotService
            ->copyFromReport(
                $sourceReport,
                $targetReportUuid
            );

        $laboratorySnapshot = array_merge(
            $this->snapshotArray(
                $sourceReport->laboratory_snapshot,
                'laboratory'
            ),
            $mediaSnapshot
        );

        $nextVersion = ((int) $sourceReport->version) + 1;
        $reportNumber = sprintf(
            '%s-A%d',
            $sourceReport->baseReportNumber(),
            $nextVersion - 1
        );

        $targetReport = LaboratoryReport::query()->create([
            'uuid' => $targetReportUuid,
            'lineage_uuid' => $sourceReport->lineage_uuid,
            'version' => $nextVersion,
            'report_number' => $reportNumber,
            'laboratory_order_id' => (
                $sourceReport->laboratory_order_id
            ),
            'supersedes_report_id' => $sourceReport->id,
            'generated_by' => $reviewer->id,
            'laboratory_snapshot' => $laboratorySnapshot,
            'patient_snapshot' => $patientSnapshot,
            'order_snapshot' => $orderSnapshot,
            'results_snapshot' => $resultsSnapshot,
            'amendment_reason' => $amendment->reason,
            'amendment_summary' => $changeSummary,
            'amended_by' => $reviewer->id,
            'amendment_actor_snapshot' => (
                $reviewerSnapshot
            ),
            'amended_at' => $approvedAt,
            'generated_at' => $approvedAt,
        ]);

        $amendment->forceFill([
            'status' => (
                LaboratoryReportAmendment::STATUS_APPROVED
            ),
            'open_source_report_id' => null,
            'target_report_id' => $targetReport->id,
            'reviewed_by' => $reviewer->id,
            'reviewer_snapshot' => $reviewerSnapshot,
            'reviewed_at' => $approvedAt,
            'rejection_reason' => null,
        ])->save();

        $this->portalAvailability->dispatch(
            $tenantId,
            $targetReport
        );

        return $amendment->fresh();
    }

    private function assertSubmitted(
        LaboratoryReportAmendment $amendment
    ): void {
        if (
            $amendment->status
            === LaboratoryReportAmendment::STATUS_SUBMITTED
            && $amendment->open_source_report_id
                === $amendment->source_report_id
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'amendment' => [
                'Only a submitted amendment can be reviewed.',
            ],
        ]);
    }

    private function assertReviewerPermission(
        TenantUser $reviewer
    ): void {
        if (
            $reviewer->hasPermission(
                TenantUserPermissionCatalog::APPROVE_LABORATORY_REPORT_AMENDMENTS
            )
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'authorization' => [
                'You are not permitted to review report amendments.',
            ],
        ]);
    }

    private function currentTenant(): Tenant
    {
        $tenant = Tenant::current();

        if ($tenant instanceof Tenant) {
            return $tenant;
        }

        throw new LogicException(
            'No active tenant is available.'
        );
    }

    private function isConcurrencyFailure(
        Throwable $exception
    ): bool {
        if (! $exception instanceof QueryException) {
            return false;
        }

        $driverCode = $exception->errorInfo[1]
            ?? null;
        $sqlState = $exception->errorInfo[0]
            ?? $exception->getCode();

        return in_array(
            $driverCode,
            [1205, 1213],
            true
        ) || $sqlState === '40001';
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotArray(
        mixed $value,
        string $name
    ): array {
        if (is_array($value)) {
            return $value;
        }

        throw ValidationException::withMessages([
            'amendment' => [
                sprintf(
                    'The stored %s snapshot is invalid.',
                    $name
                ),
            ],
        ]);
    }
}
