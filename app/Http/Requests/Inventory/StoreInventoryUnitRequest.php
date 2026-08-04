<?php

namespace App\Http\Requests\Inventory;

use App\Models\InventoryUnit;
use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryUnitRequest extends FormRequest
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
            'symbol' => trim((string) $this->input('symbol')),
        ]);
    }

    public function rules(): array
    {
        $unit = $this->route('unit');
        $id = $unit instanceof InventoryUnit ? $unit->getKey() : null;

        return [
            'code' => ['required', 'string', 'max:40', Rule::unique('inventory_units', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:120'],
            'symbol' => ['required', 'string', 'max:30'],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:6'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
