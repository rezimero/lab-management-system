<?php

namespace App\Http\Requests\Inventory;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexInventoryStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(TenantUserPermissionCatalog::VIEW_INVENTORY);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('search')) {
            $search = trim((string) $this->input('search'));
            $this->merge(['search' => $search === '' ? null : $search]);
        }
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'branch_uuid' => ['nullable', 'uuid'],
            'category_uuid' => ['nullable', 'uuid'],
            'item_type' => ['nullable', Rule::in(['reagent', 'consumable', 'general'])],
            'movement_type' => ['nullable', Rule::in(['receipt', 'adjustment_in', 'adjustment_out', 'consumption'])],
            'supplier_uuid' => ['nullable', 'uuid'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'expiry_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
