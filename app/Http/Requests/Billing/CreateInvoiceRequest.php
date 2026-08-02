<?php

namespace App\Http\Requests\Billing;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInvoiceRequest extends FormRequest
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
            'laboratory_order_uuid' => [
                'required', 'uuid', Rule::exists('laboratory_orders', 'uuid'),
            ],
            'corporate_account_uuid' => [
                'nullable', 'uuid', Rule::exists('corporate_accounts', 'uuid'),
            ],
            'discount_amount' => [
                'sometimes', 'numeric', 'decimal:0,2', 'min:0',
                'max:999999999999.99',
            ],
            'tax_amount' => [
                'sometimes', 'numeric', 'decimal:0,2', 'min:0',
                'max:999999999999.99',
            ],
            'due_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
