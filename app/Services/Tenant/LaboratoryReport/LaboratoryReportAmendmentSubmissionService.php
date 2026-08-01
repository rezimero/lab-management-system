<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Events\Tenant\LaboratoryReport\LaboratoryReportAmendmentSubmitted;
use App\Models\LaboratoryOrder;
use App\Models\LaboratoryReport;
use App\Models\LaboratoryReportAmendment;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class LaboratoryReportAmendmentSubmissionService
{
    public function __construct(
        private readonly LaboratoryReportAmendmentPayloadBuilder $payloadBuilder,
        private readonly LaboratoryReportAmendmentActorSnapshotFactory $actorSnapshotFactory
    ) {}

    /**
     * Submit a controlled amendment against one exact current report.
     *
     * @param  array<string, mixed>  $input
     */
    public function submit(
        string $sourceReportUuid,
        array $input,
        TenantUser $requester
    ): LaboratoryReportAmendment {
        $this->assertRequesterPermission($requester);

        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            throw new LogicException(
                'No active tenant is available.'
            );
        }

        $requesterSnapshot = $this
            ->actorSnapshotFactory
            ->make($requester);

        try {
            $amendment = DB::connection('tenant')->transaction(
                function () use (
                    $sourceReportUuid,
                    $input,
                    $requester,
                    $requesterSnapshot
                ): LaboratoryReportAmendment {
                    $sourceReport = LaboratoryReport::query()
                        ->where('uuid', $sourceReportUuid)
                        ->lockForUpdate()
                        ->firstOrFail();

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
                                'Only the current report version can be amended.',
                            ],
                        ]);
                    }

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

                    $openExists = LaboratoryReportAmendment::query()
                        ->where(
                            'open_source_report_id',
                            $sourceReport->id
                        )
                        ->exists();

                    if ($openExists) {
                        throw ValidationException::withMessages([
                            'laboratory_report' => [
                                'A submitted amendment already exists for this report.',
                            ],
                        ]);
                    }

                    $reason = trim(
                        (string) ($input['reason'] ?? '')
                    );

                    if (
                        mb_strlen($reason) < 10
                        || preg_match(
                            '/[\pL\pN]{3,}/u',
                            $reason
                        ) !== 1
                    ) {
                        throw ValidationException::withMessages([
                            'reason' => [
                                'Provide a meaningful amendment reason.',
                            ],
                        ]);
                    }

                    $payload = $this->payloadBuilder->build(
                        $sourceReport,
                        $input
                    );

                    return LaboratoryReportAmendment::query()
                        ->create([
                            'source_report_id' => $sourceReport->id,
                            'open_source_report_id' => (
                                $sourceReport->id
                            ),
                            'target_report_id' => null,
                            'status' => (
                                LaboratoryReportAmendment::STATUS_SUBMITTED
                            ),
                            'reason' => $reason,
                            'change_summary' => (
                                $payload['change_summary']
                            ),
                            'proposed_patient_snapshot' => (
                                $payload['patient_snapshot']
                            ),
                            'proposed_order_snapshot' => (
                                $payload['order_snapshot']
                            ),
                            'proposed_results_snapshot' => (
                                $payload['results_snapshot']
                            ),
                            'requested_by' => $requester->id,
                            'requester_snapshot' => (
                                $requesterSnapshot
                            ),
                            'requested_at' => now(),
                        ]);
                },
                3
            );
        } catch (QueryException $exception) {
            if (
                $exception->getCode() === '23000'
                && str_contains(
                    strtolower($exception->getMessage()),
                    'open_source'
                )
            ) {
                throw ValidationException::withMessages([
                    'laboratory_report' => [
                        'A submitted amendment already exists for this report.',
                    ],
                ]);
            }

            throw $exception;
        }

        $amendment->load([
            'sourceReport',
            'targetReport',
            'requestedBy',
            'reviewedBy',
            'resultAmendments.testResult',
        ]);

        LaboratoryReportAmendmentSubmitted::dispatch(
            $tenant->id,
            $amendment->uuid,
            $amendment->sourceReport->uuid,
            $requesterSnapshot
        );

        return $amendment;
    }

    private function assertRequesterPermission(
        TenantUser $requester
    ): void {
        if (
            $requester->hasPermission(
                TenantUserPermissionCatalog::REQUEST_LABORATORY_REPORT_AMENDMENTS
            )
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'authorization' => [
                'You are not permitted to request report amendments.',
            ],
        ]);
    }
}
