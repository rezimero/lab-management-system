<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'parent' => $this->whenLoaded('parent', fn (): ?array => $this->parent === null ? null : [
                'uuid' => $this->parent->uuid,
                'code' => $this->parent->code,
                'name' => $this->parent->name,
            ]),
            'email' => $this->email,
            'phone' => $this->phone,
            'address_line_1' => $this->address_line_1,
            'address_line_2' => $this->address_line_2,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'is_head_office' => (bool) $this->is_head_office,
            'is_active' => (bool) $this->is_active,
            'users_count' => $this->whenCounted('users'),
            'collection_orders_count' => $this->whenCounted('collectionOrders'),
            'processing_orders_count' => $this->whenCounted('processingOrders'),
            'users' => $this->whenLoaded('users', fn () => $this->users->map(fn ($user): array => [
                'id' => $user->getKey(),
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_primary' => (bool) $user->pivot->is_primary,
            ])->values()),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
