<?php

namespace App\Http\Resources;

use App\Models\LaboratoryReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryReportResource extends JsonResource
{
    /**
     * Transform an immutable laboratory report.
     */
    public function toArray(
        Request $request
    ): array {
        $successor = $this->relationLoaded('supersededBy')
            ? $this->supersededBy
            : $this->supersededBy()->first();

        $isCurrent = $successor === null;
        $version = (int) ($this->version ?? 1);

        return [
            'uuid' => $this->uuid,
            'lineage_uuid' => $this->lineage_uuid
                ?? $this->uuid,
            'version' => $version,
            'amendment_number' => max(0, $version - 1),
            'base_report_number' => (
                $this->resource->baseReportNumber()
            ),
            'report_number' => $this->report_number,
            'lifecycle_status' => $this->lifecycleStatus(
                $version,
                $isCurrent
            ),
            'is_current' => $isCurrent,

            'source_order_uuid' => (
                $this->order_snapshot['uuid']
                ?? null
            ),

            'laboratory' => (
                $this->laboratory_snapshot
                ?? []
            ),

            'patient' => (
                $this->patient_snapshot
                ?? []
            ),

            'order' => (
                $this->order_snapshot
                ?? []
            ),

            'results' => (
                $this->results_snapshot['items']
                ?? []
            ),

            'supersedes' => $this->when(
                $this->relationLoaded('supersedes'),
                fn (): ?array => $this->reportSummary(
                    $this->supersedes
                )
            ),

            'superseded_by' => $this->when(
                $this->relationLoaded('supersededBy'),
                fn (): ?array => $this->reportSummary(
                    $this->supersededBy
                )
            ),

            'amendment' => $version > 1
                ? [
                    'reason' => $this->amendment_reason,
                    'summary' => (
                        $this->amendment_summary
                        ?? []
                    ),
                    'amended_at' => (
                        $this->amended_at
                            ?->toISOString()
                    ),
                    'actor' => (
                        $this->amendment_actor_snapshot
                    ),
                    'authorized_by' => (
                        $this->amendment_actor_snapshot
                    ),
                ]
                : null,

            'lineage' => $this->when(
                $this->relationLoaded('lineageReports'),
                function (): array {
                    return $this->lineageReports
                        ->sortBy('version')
                        ->values()
                        ->map(
                            fn (
                                LaboratoryReport $report
                            ): array => (
                                $this->reportSummary($report)
                            )
                        )
                        ->all();
                }
            ),

            'generated_by' => $this->when(
                $this->relationLoaded(
                    'generatedBy'
                ),
                fn (): ?array => (
                    $this->generatedBy === null
                        ? null
                        : [
                            'id' => (
                                $this->generatedBy->id
                            ),
                            'name' => (
                                $this->generatedBy->name
                            ),
                            'email' => (
                                $this->generatedBy->email
                            ),
                        ]
                )
            ),

            'generated_at' => (
                $this->generated_at
                    ?->toISOString()
            ),

            'created_at' => (
                $this->created_at
                    ?->toISOString()
            ),
        ];
    }

    private function lifecycleStatus(
        int $version,
        bool $isCurrent
    ): string {
        if (! $isCurrent) {
            return 'superseded';
        }

        return $version > 1
            ? 'amended'
            : 'issued';
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
            'version' => (int) ($report->version ?? 1),
            'generated_at' => (
                $report->generated_at
                    ?->toISOString()
            ),
        ];
    }
}
