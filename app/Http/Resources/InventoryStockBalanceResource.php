<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryStockBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'branch' => [
                'uuid' => $this->branch->uuid,
                'code' => $this->branch->code,
                'name' => $this->branch->name,
            ],
            'item' => (new InventoryItemResource($this->item))->resolve($request),
            'lot' => [
                'uuid' => $this->lot->uuid,
                'internal_lot_code' => $this->lot->internal_lot_code,
                'external_lot_number' => $this->lot->external_lot_number,
                'batch_number' => $this->lot->batch_number,
                'expires_at' => $this->lot->expires_at?->toDateString(),
                'is_expired' => $this->lot->expires_at?->isBefore(today()) ?? false,
            ],
            'quantity_on_hand' => (string) $this->quantity_on_hand,
            'last_movement_at' => $this->last_movement_at?->toISOString(),
        ];
    }
}
