<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryAdjustmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'adjustment_number' => $this->adjustment_number,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'adjusted_at' => $this->adjusted_at?->toISOString(),
            'branch' => $this->whenLoaded('branch', fn (): array => [
                'uuid' => $this->branch->uuid,
                'code' => $this->branch->code,
                'name' => $this->branch->name,
            ]),
            'created_by' => $this->whenLoaded('creator', fn (): ?array => $this->creator === null ? null : [
                'id' => $this->creator->getKey(),
                'name' => $this->creator->name,
            ]),
            'movements_count' => $this->whenCounted('movements'),
            'movements' => $this->whenLoaded(
                'movements',
                fn () => InventoryMovementResource::collection($this->movements)->resolve($request)
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
