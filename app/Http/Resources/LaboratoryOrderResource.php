<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryOrderResource extends JsonResource
{
    /**
     * Transform a laboratory order.
     */
    public function toArray(
        Request $request
    ): array {
        return [
            'uuid' => $this->uuid,
            'order_number' => (
                $this->order_number
            ),

            'patient' => $this->when(
                $this->relationLoaded('patient'),
                fn (): array => [
                    'id' => $this->patient->id,
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
                ]
            ),

            'ordered_by' => $this->when(
                $this->relationLoaded(
                    'orderedBy'
                ),
                fn (): ?array => (
                    $this->orderedBy === null
                        ? null
                        : [
                            'id' => (
                                $this->orderedBy->id
                            ),
                            'name' => (
                                $this->orderedBy->name
                            ),
                            'email' => (
                                $this->orderedBy->email
                            ),
                        ]
                )
            ),

            'referring_clinician' => (
                $this->referring_clinician
            ),
            'priority' => $this->priority,
            'status' => $this->status,
            'clinical_notes' => (
                $this->clinical_notes
            ),
            'total_amount' => (
                $this->total_amount
            ),
            'ordered_at' => (
                $this->ordered_at
                    ?->toISOString()
            ),
            'sample_collected_at' => (
                $this->sample_collected_at
                    ?->toISOString()
            ),
            'completed_at' => (
                $this->completed_at
                    ?->toISOString()
            ),
            'cancelled_at' => (
                $this->cancelled_at
                    ?->toISOString()
            ),

            'items' => (
                LaboratoryOrderItemResource::collection(
                    $this->whenLoaded('items')
                )
            ),

            'specimens' => (
                SpecimenResource::collection(
                    $this->whenLoaded('specimens')
                )
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
}
