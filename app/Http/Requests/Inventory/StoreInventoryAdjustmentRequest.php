<?php

namespace App\Http\Requests\Inventory;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(TenantUserPermissionCatalog::ADJUST_INVENTORY_STOCK);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('reason')) {
            $this->merge(['reason' => trim((string) $this->input('reason'))]);
        }
    }

    public function rules(): array
    {
        return [
            'branch_uuid' => ['required', 'uuid'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'adjusted_at' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.lot_uuid' => ['required', 'uuid', 'distinct'],
            'lines.*.direction' => ['required', Rule::in(['in', 'out'])],
            'lines.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
        ];
    }
}
