<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'sku' => $this->sku,
            'name' => $this->name,
            'item_type' => $this->item_type,
            'category' => $this->whenLoaded('category', fn (): array => [
                'uuid' => $this->category->uuid,
                'code' => $this->category->code,
                'name' => $this->category->name,
            ]),
            'unit' => $this->whenLoaded('unit', fn (): array => [
                'uuid' => $this->unit->uuid,
                'code' => $this->unit->code,
                'name' => $this->unit->name,
                'symbol' => $this->unit->symbol,
                'decimal_places' => (int) $this->unit->decimal_places,
            ]),
            'manufacturer' => $this->manufacturer,
            'catalogue_number' => $this->catalogue_number,
            'description' => $this->description,
            'tracks_lots' => (bool) $this->tracks_lots,
            'tracks_expiry' => (bool) $this->tracks_expiry,
            'default_reorder_level' => (string) $this->default_reorder_level,
            'is_active' => (bool) $this->is_active,
            'branches' => $this->whenLoaded('branchSettings', fn () => $this->branchSettings->map(
                fn ($setting): array => [
                    'branch' => [
                        'uuid' => $setting->branch->uuid,
                        'code' => $setting->branch->code,
                        'name' => $setting->branch->name,
                        'type' => $setting->branch->type,
                        'is_active' => (bool) $setting->branch->is_active,
                    ],
                    'reorder_level' => $setting->reorder_level === null
                        ? null
                        : (string) $setting->reorder_level,
                    'is_active' => (bool) $setting->is_active,
                ]
            )->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
