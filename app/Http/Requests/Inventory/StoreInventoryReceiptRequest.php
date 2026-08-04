<?php

namespace App\Http\Requests\Inventory;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(TenantUserPermissionCatalog::RECEIVE_INVENTORY_STOCK);
    }

    public function rules(): array
    {
        return [
            'branch_uuid' => ['required', 'uuid'],
            'supplier_uuid' => ['nullable', 'uuid'],
            'supplier_reference' => ['nullable', 'string', 'max:160'],
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.item_uuid' => ['required', 'uuid'],
            'lines.*.external_lot_number' => ['nullable', 'string', 'max:160'],
            'lines.*.batch_number' => ['nullable', 'string', 'max:160'],
            'lines.*.manufactured_at' => ['nullable', 'date'],
            'lines.*.expires_at' => ['nullable', 'date'],
            'lines.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'lines.*.unit_cost' => ['nullable', 'decimal:0,4', 'min:0'],
            'lines.*.currency' => ['nullable', 'string', 'size:3'],
        ];
    }
}
