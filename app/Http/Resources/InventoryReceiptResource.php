<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'receipt_number' => $this->receipt_number,
            'branch' => $this->whenLoaded('branch', fn (): array => [
                'uuid' => $this->branch->uuid,
                'code' => $this->branch->code,
                'name' => $this->branch->name,
            ]),
            'supplier' => $this->whenLoaded('supplier', fn (): ?array => $this->supplier === null ? null : [
                'uuid' => $this->supplier->uuid,
                'code' => $this->supplier->code,
                'name' => $this->supplier->name,
            ]),
            'supplier_reference' => $this->supplier_reference,
            'received_at' => $this->received_at?->toISOString(),
            'notes' => $this->notes,
            'received_by' => $this->whenLoaded('receiver', fn (): ?array => $this->receiver === null ? null : [
                'id' => $this->receiver->getKey(),
                'name' => $this->receiver->name,
            ]),
            'lots_count' => $this->whenCounted('lots'),
            'lots' => $this->whenLoaded('lots', fn () => $this->lots->map(fn ($lot): array => [
                'uuid' => $lot->uuid,
                'internal_lot_code' => $lot->internal_lot_code,
                'external_lot_number' => $lot->external_lot_number,
                'batch_number' => $lot->batch_number,
                'manufactured_at' => $lot->manufactured_at?->toDateString(),
                'expires_at' => $lot->expires_at?->toDateString(),
                'quantity_received' => (string) $lot->quantity_received,
                'unit_cost' => $lot->unit_cost === null ? null : (string) $lot->unit_cost,
                'currency' => $lot->currency,
                'item' => [
                    'uuid' => $lot->item->uuid,
                    'sku' => $lot->item->sku,
                    'name' => $lot->item->name,
                    'unit_symbol' => $lot->item->unit?->symbol,
                ],
            ])->values()),
            'movements' => $this->whenLoaded(
                'movements',
                fn () => InventoryMovementResource::collection($this->movements)->resolve($request)
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
