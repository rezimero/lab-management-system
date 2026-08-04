<?php

namespace App\Http\Requests\Inventory;

use App\Models\Supplier;
use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(TenantUserPermissionCatalog::MANAGE_INVENTORY_CATALOG);
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach (['code', 'name', 'contact_name', 'email', 'phone', 'address', 'tax_identifier'] as $field) {
            if ($this->has($field)) {
                $value = trim((string) $this->input($field));
                $payload[$field] = $value === '' ? null : $value;
            }
        }

        if (isset($payload['code'])) {
            $payload['code'] = strtoupper($payload['code']);
        }

        if (isset($payload['email'])) {
            $payload['email'] = strtolower($payload['email']);
        }

        $this->merge($payload);
    }

    public function rules(): array
    {
        $supplier = $this->route('supplier');
        $id = $supplier instanceof Supplier ? $supplier->getKey() : null;

        return [
            'code' => ['required', 'string', 'max:60', Rule::unique('suppliers', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:180'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string'],
            'tax_identifier' => ['nullable', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
