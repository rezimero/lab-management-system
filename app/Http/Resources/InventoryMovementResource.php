<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'movement_type' => $this->movement_type,
            'quantity_delta' => (string) $this->quantity_delta,
            'quantity_after' => (string) $this->quantity_after,
            'reason' => $this->reason,
            'occurred_at' => $this->occurred_at?->toISOString(),
            'branch' => $this->whenLoaded('branch', fn (): array => [
                'uuid' => $this->branch->uuid,
                'code' => $this->branch->code,
                'name' => $this->branch->name,
            ]),
            'item' => $this->whenLoaded('item', fn (): array => [
                'uuid' => $this->item->uuid,
                'sku' => $this->item->sku,
                'name' => $this->item->name,
                'unit_symbol' => $this->item->unit?->symbol,
            ]),
            'lot' => $this->whenLoaded('lot', fn (): array => [
                'uuid' => $this->lot->uuid,
                'internal_lot_code' => $this->lot->internal_lot_code,
                'external_lot_number' => $this->lot->external_lot_number,
                'batch_number' => $this->lot->batch_number,
                'expires_at' => $this->lot->expires_at?->toDateString(),
            ]),
            'performed_by' => $this->whenLoaded('performer', fn (): ?array => $this->performer === null ? null : [
                'id' => $this->performer->getKey(),
                'name' => $this->performer->name,
                'email' => $this->performer->email,
            ]),
        ];
    }
}
