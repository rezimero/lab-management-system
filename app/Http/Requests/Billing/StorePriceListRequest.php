<?php

namespace App\Http\Requests\Billing;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePriceListRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::MANAGE_PRICE_LISTS
            );
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:60', Rule::unique('price_lists', 'code')],
            'name' => ['required', 'string', 'max:180'],
            'currency' => ['required', 'string', 'size:3'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'items' => ['sometimes', 'array', 'max:500'],
            'items.*.lab_test_id' => ['required', 'integer', 'distinct', Rule::exists('lab_tests', 'id')],
            'items.*.price' => [
                'required', 'numeric', 'decimal:0,2', 'min:0',
                'max:999999999999.99',
            ],
        ];
    }
}
