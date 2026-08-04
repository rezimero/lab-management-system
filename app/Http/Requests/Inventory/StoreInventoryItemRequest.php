<?php

namespace App\Http\Requests\Inventory;

use App\Models\InventoryItem;
use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(TenantUserPermissionCatalog::MANAGE_INVENTORY_CATALOG);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sku' => strtoupper(trim((string) $this->input('sku'))),
            'name' => trim((string) $this->input('name')),
        ]);
    }

    public function rules(): array
    {
        $item = $this->route('item');
        $id = $item instanceof InventoryItem ? $item->getKey() : null;

        return [
            'sku' => ['required', 'string', 'max:80', Rule::unique('inventory_items', 'sku')->ignore($id)],
            'name' => ['required', 'string', 'max:180'],
            'item_type' => ['required', Rule::in(['reagent', 'consumable', 'general'])],
            'category_uuid' => ['required', 'uuid'],
            'unit_uuid' => ['required', 'uuid'],
            'manufacturer' => ['nullable', 'string', 'max:180'],
            'catalogue_number' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string'],
            'tracks_lots' => ['sometimes', 'boolean'],
            'tracks_expiry' => ['sometimes', 'boolean'],
            'default_reorder_level' => ['required', 'decimal:0,6', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'branches' => ['sometimes', 'array'],
            'branches.*.branch_uuid' => ['required', 'uuid', 'distinct'],
            'branches.*.reorder_level' => ['nullable', 'decimal:0,6', 'min:0'],
            'branches.*.is_active' => ['sometimes', 'boolean'],
        ];
    }
}
