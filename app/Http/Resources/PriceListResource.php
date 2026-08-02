<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'currency' => $this->currency,
            'is_default' => (bool) $this->is_default,
            'is_active' => (bool) $this->is_active,
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_to' => $this->effective_to?->toDateString(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'lab_test_id' => $item->lab_test_id,
                'lab_test_name' => $item->labTest?->name,
                'price' => $item->price,
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
