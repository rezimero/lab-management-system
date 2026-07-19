<?php

namespace App\Http\Resources;

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
        return [
            'uuid' => $this->uuid,
            'report_number' => $this->report_number,

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
}
