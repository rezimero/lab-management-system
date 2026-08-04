<?php

namespace App\Http\Requests\Inventory;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class SyncLabTestInventoryRequirementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(TenantUserPermissionCatalog::MANAGE_TEST_INVENTORY_REQUIREMENTS);
    }

    public function rules(): array
    {
        return [
            'requirements' => ['present', 'array', 'max:100'],
            'requirements.*.item_uuid' => ['required', 'uuid', 'distinct'],
            'requirements.*.quantity' => ['required', 'decimal:0,6', 'gt:0'],
            'requirements.*.is_active' => ['sometimes', 'boolean'],
            'requirements.*.notes' => ['nullable', 'string'],
        ];
    }
}
