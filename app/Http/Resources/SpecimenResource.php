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
