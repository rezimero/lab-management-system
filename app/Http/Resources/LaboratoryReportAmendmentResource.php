<?php

namespace App\Http\Resources;

use App\Models\LaboratoryReport;
use App\Models\TestResultAmendment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryReportAmendmentResource extends JsonResource
{
    /**
     * Transform amendment workflow evidence.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status,
            'reason' => $this->reason,
            'change_summary' => (
                $this->change_summary
                ?? []
            ),
            'proposed_patient_snapshot' => (
                $this->proposed_patient_snapshot
                ?? []
            ),
            'proposed_order_snapshot' => (
                $this->proposed_order_snapshot
                ?? []
            ),
            'proposed_results_snapshot' => (
                $this->proposed_results_snapshot
                ?? []
            ),
            'requester' => $this->requester_snapshot,
            'requested_at' => (
                $this->requested_at
                    ?->toISOString()
            ),
            'reviewer' => $this->reviewer_snapshot,
            'reviewed_at' => (
                $this->reviewed_at
                    ?->toISOString()
            ),
            'rejection_reason' => (
                $this->rejection_reason
            ),
            'is_open' => (
                $this->open_source_report_id !== null
            ),

            'source_report' => $this->when(
                $this->relationLoaded('sourceReport'),
                fn (): ?array => $this->reportSummary(
                    $this->sourceReport
                )
            ),

            'target_report' => $this->when(
                $this->relationLoaded('targetReport'),
                fn (): ?array => $this->reportSummary(
                    $this->targetReport
                )
            ),

            'result_amendments' => $this->when(
                $this->relationLoaded('resultAmendments'),
                fn (): array => $this->resultAmendments
                    ->sortBy('version')
                    ->values()
                    ->map(
                        fn (
                            TestResultAmendment $amendment
                        ): array => [
                            'uuid' => $amendment->uuid,
                            'test_result_uuid' => (
                                $amendment->relationLoaded(
                                    'testResult'
                                )
                                    ? $amendment->testResult?->uuid
                                    : null
                            ),
                            'version' => $amendment->version,
                            'source_payload_hash' => (
                                $amendment->source_payload_hash
                            ),
                            'corrected_results_data' => (
                                $amendment->corrected_results_data
                            ),
                            'corrected_notes' => (
                                $amendment->corrected_notes
                            ),
                            'reason' => $amendment->reason,
                            'amended_by' => (
                                $amendment
                                    ->amendment_actor_snapshot
                            ),
                            'approved_by' => (
                                $amendment
                                    ->approval_actor_snapshot
                            ),
                            'approved_at' => (
                                $amendment->approved_at
                                    ?->toISOString()
                            ),
                        ]
                    )
                    ->all()
            ),

            'created_at' => (
                $this->created_at
                    ?->toISOString()
            ),
            'updated_at' => (
                $this->updated_at
                    ?->toISOString()
            ),
        ];
    }

    private function reportSummary(
        ?LaboratoryReport $report
    ): ?array {
        if ($report === null) {
            return null;
        }

        return [
            'uuid' => $report->uuid,
            'report_number' => $report->report_number,
            'lineage_uuid' => $report->lineage_uuid,
            'version' => (int) $report->version,
            'generated_at' => (
                $report->generated_at
                    ?->toISOString()
            ),
        ];
    }
}
