<?php

namespace App\Http\Resources\Portal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicianPortalOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'order_number' => $this->order_number,
            'priority' => $this->priority,
            'status' => $this->status,
            'referring_clinician' => $this->referring_clinician,
            'ordered_at' => $this->ordered_at?->toISOString(),
            'sample_collected_at' => (
                $this->sample_collected_at?->toISOString()
            ),
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),

            'patient' => $this->when(
                $this->relationLoaded('patient'),
                fn (): ?array => (
                    $this->patient === null
                        ? null
                        : [
                            'medical_record_number' => (
                                $this->patient
                                    ->medical_record_number
                            ),
                            'first_name' => (
                                $this->patient->first_name
                            ),
                            'last_name' => (
                                $this->patient->last_name
                            ),
                            'full_name' => (
                                $this->patient->full_name
                            ),
                            'date_of_birth' => (
                                $this->patient
                                    ->date_of_birth
                                    ?->toDateString()
                            ),
                            'gender' => $this->patient->gender,
                        ]
                )
            ),

            'items' => $this->when(
                $this->relationLoaded('items'),
                fn (): array => (
                    $this->items
                        ->sortBy('id')
                        ->values()
                        ->map(
                            fn ($item): array => [
                                'uuid' => $item->uuid,
                                'test_name' => (
                                    $item->test_name_snapshot
                                ),
                                'category' => (
                                    $item->test_category_snapshot
                                ),
                                'status' => $item->status,
                                'turnaround_time_hours' => (
                                    $item
                                        ->turnaround_time_hours_snapshot
                                ),
                            ]
                        )
                        ->all()
                )
            ),

            'report' => $this->when(
                $this->relationLoaded('report'),
                fn (): ?array => (
                    $this->report === null
                        ? null
                        : $this->reportSummary(
                            $this->report
                        )
                )
            ),
        ];
    }

    private function reportSummary(
        mixed $report
    ): array {
        $version = max(
            1,
            (int) ($report->version ?? 1)
        );

        return [
            'uuid' => $report->uuid,
            'report_number' => $report->report_number,
            'version' => $version,
            'lifecycle_status' => $version > 1
                ? 'amended'
                : 'issued',
            'generated_at' => (
                $report->generated_at?->toISOString()
            ),
        ];
    }
}
