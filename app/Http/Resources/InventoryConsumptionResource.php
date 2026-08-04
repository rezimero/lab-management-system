<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryConsumptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'consumption_number' => $this->consumption_number,
            'consumed_at' => $this->consumed_at?->toISOString(),
            'notes' => $this->notes,
            'branch' => $this->whenLoaded('branch', fn (): array => [
                'uuid' => $this->branch->uuid,
                'code' => $this->branch->code,
                'name' => $this->branch->name,
            ]),
            'order_item' => $this->whenLoaded('orderItem', fn (): array => [
                'uuid' => $this->orderItem->uuid,
                'test_name' => $this->orderItem->test_name_snapshot,
                'order_number' => $this->orderItem->order?->order_number,
            ]),
            'recorded_by' => $this->whenLoaded('recorder', fn (): ?array => $this->recorder === null ? null : [
                'id' => $this->recorder->getKey(),
                'name' => $this->recorder->name,
            ]),
            'movements' => $this->whenLoaded(
                'movements',
                fn () => InventoryMovementResource::collection($this->movements)->resolve($request)
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
