<?php

namespace App\Http\Requests\Billing;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCorporateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::MANAGE_BILLING
            );
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:60', Rule::unique('corporate_accounts', 'code')],
            'name' => ['required', 'string', 'max:180'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'billing_address' => ['nullable', 'string', 'max:3000'],
            'payment_terms_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'credit_limit' => [
                'nullable', 'numeric', 'decimal:0,2', 'min:0',
                'max:999999999999.99',
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
