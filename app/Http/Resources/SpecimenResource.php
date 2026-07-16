<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpecimenResource extends JsonResource
{
    /**
     * Transform a specimen.
     */
    public function toArray(
        Request $request
    ): array {
        return [
            'uuid' => $this->uuid,
            'accession_number' => (
                $this->accession_number
            ),
            'specimen_type' => (
                $this->specimen_type
            ),
            'container_type' => (
                $this->container_type
            ),
            'collection_site' => (
                $this->collection_site
            ),
            'volume' => $this->volume,
            'volume_unit' => $this->volume_unit,
            'status' => $this->status,

            'order' => $this->when(
                $this->relationLoaded('order'),
                function (): ?array {
                    if ($this->order === null) {
                        return null;
                    }

                    $patient = null;

                    if (
                        $this->order
                            ->relationLoaded('patient')
                        && $this->order->patient !== null
                    ) {
                        $patient = [
                            'id' => (
                                $this->order->patient->id
                            ),
                            'medical_record_number' => (
                                $this->order
                                    ->patient
                                    ->medical_record_number
                            ),
                            'first_name' => (
                                $this->order
                                    ->patient
                                    ->first_name
                            ),
                            'last_name' => (
                                $this->order
                                    ->patient
                                    ->last_name
                            ),
                            'full_name' => (
                                $this->order
                                    ->patient
                                    ->full_name
                            ),
                        ];
                    }

                    return [
                        'uuid' => $this->order->uuid,
                        'order_number' => (
                            $this->order->order_number
                        ),
                        'priority' => (
                            $this->order->priority
                        ),
                        'status' => (
                            $this->order->status
                        ),
                        'patient' => $patient,
                    ];
                }
            ),

            'collected_by' => $this->when(
                $this->relationLoaded(
                    'collectedBy'
                ),
                fn (): ?array => (
                    $this->collectedBy === null
                        ? null
                        : [
                            'id' => (
                                $this->collectedBy->id
                            ),
                            'name' => (
                                $this->collectedBy->name
                            ),
                            'email' => (
                                $this->collectedBy->email
                            ),
                        ]
                )
            ),

            'order_item_uuids' => $this->when(
                $this->relationLoaded(
                    'orderItems'
                ),
                fn (): array => (
                    $this->orderItems
                        ->pluck('uuid')
                        ->values()
                        ->all()
                )
            ),

            'order_items' => $this->when(
                $this->relationLoaded(
                    'orderItems'
                ),
                fn (): array => (
                    $this->orderItems
                        ->map(
                            fn ($item): array => [
                                'uuid' => $item->uuid,
                                'test_name' => (
                                    $item
                                        ->test_name_snapshot
                                ),
                                'test_category' => (
                                    $item
                                        ->test_category_snapshot
                                ),
                                'price' => (
                                    $item->price_snapshot
                                ),
                                'status' => $item->status,
                            ]
                        )
                        ->values()
                        ->all()
                )
            ),

            'collected_at' => (
                $this->collected_at
                    ?->toISOString()
            ),
            'received_at' => (
                $this->received_at
                    ?->toISOString()
            ),
            'rejected_at' => (
                $this->rejected_at
                    ?->toISOString()
            ),
            'rejection_reason' => (
                $this->rejection_reason
            ),
            'notes' => $this->notes,
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