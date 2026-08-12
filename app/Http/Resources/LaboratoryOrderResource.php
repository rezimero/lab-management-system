<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'order_number' => $this->order_number,
            'patient' => $this->when(
                $this->relationLoaded('patient'),
                fn (): array => [
                    'id' => $this->patient->id,
                    'medical_record_number' => $this->patient->medical_record_number,
                    'first_name' => $this->patient->first_name,
                    'last_name' => $this->patient->last_name,
                    'full_name' => $this->patient->full_name,
                ]
            ),
            'ordered_by' => $this->when(
                $this->relationLoaded('orderedBy'),
                fn (): ?array => $this->orderedBy === null ? null : [
                    'id' => $this->orderedBy->id,
                    'name' => $this->orderedBy->name,
                    'email' => $this->orderedBy->email,
                ]
            ),
            'collection_branch' => $this->when(
                $this->relationLoaded('collectionBranch'),
                fn (): ?array => $this->collectionBranch === null ? null : [
                    'uuid' => $this->collectionBranch->uuid,
                    'code' => $this->collectionBranch->code,
                    'name' => $this->collectionBranch->name,
                    'type' => $this->collectionBranch->type,
                ]
            ),
            'processing_branch' => $this->when(
                $this->relationLoaded('processingBranch'),
                fn (): ?array => $this->processingBranch === null ? null : [
                    'uuid' => $this->processingBranch->uuid,
                    'code' => $this->processingBranch->code,
                    'name' => $this->processingBranch->name,
                    'type' => $this->processingBranch->type,
                ]
            ),
            'price_list' => $this->when(
                $this->relationLoaded('priceList'),
                fn (): ?array => $this->priceList === null ? null : [
                    'uuid' => $this->priceList->uuid,
                    'code' => $this->priceList->code,
                    'name' => $this->priceList->name,
                    'currency' => $this->priceList->currency,
                ]
            ),
            'invoice' => $this->when(
                $this->relationLoaded('invoice'),
                fn (): ?array => $this->invoice === null ? null : [
                    'uuid' => $this->invoice->uuid,
                    'invoice_number' => $this->invoice->invoice_number,
                    'status' => $this->invoice->status,
                    'balance_due' => $this->invoice->balance_due,
                ]
            ),
            'referring_clinician' => $this->referring_clinician,
            'referring_clinician_record' => $this->when(
                $this->relationLoaded('referringClinician'),
                fn (): ?array => (
                    $this->referringClinician === null
                        ? null
                        : [
                            'uuid' => $this->referringClinician->uuid,
                            'name' => $this->referringClinician->name,
                            'email' => $this->referringClinician->email,
                            'phone' => $this->referringClinician->phone,
                            'organisation' => $this->referringClinician->organisation,
                            'registration_number' => $this->referringClinician->registration_number,
                            'specialty' => $this->referringClinician->specialty,
                            'is_active' => (bool) $this->referringClinician->is_active,
                        ]
                )
            ),
            'priority' => $this->priority,
            'status' => $this->status,
            'clinical_notes' => $this->clinical_notes,
            'total_amount' => $this->total_amount,
            'ordered_at' => $this->ordered_at?->toISOString(),
            'sample_collected_at' => $this->sample_collected_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'items' => LaboratoryOrderItemResource::collection($this->whenLoaded('items')),
            'specimens' => SpecimenResource::collection($this->whenLoaded('specimens')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
