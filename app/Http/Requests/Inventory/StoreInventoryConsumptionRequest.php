<?php

namespace App\Http\Requests\Inventory;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryConsumptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(TenantUserPermissionCatalog::RECORD_INVENTORY_CONSUMPTION);
    }

    public function rules(): array
    {
        return [
            'consumed_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.lot_uuid' => ['required', 'uuid', 'distinct'],
            'lines.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
        ];
    }
}
