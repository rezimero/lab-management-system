<?php

namespace App\Http\Requests\Inventory;

use App\Models\InventoryCategory;
use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryCategoryRequest extends FormRequest
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
            'code' => strtoupper(trim((string) $this->input('code'))),
            'name' => trim((string) $this->input('name')),
        ]);
    }

    public function rules(): array
    {
        $category = $this->route('category');
        $id = $category instanceof InventoryCategory ? $category->getKey() : null;

        return [
            'code' => ['required', 'string', 'max:60', Rule::unique('inventory_categories', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
