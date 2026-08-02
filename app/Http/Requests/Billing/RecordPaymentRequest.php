<?php

namespace App\Http\Requests\Billing;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class RecordPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::RECORD_PAYMENTS
            );
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required', 'numeric', 'decimal:0,2', 'gt:0',
                'max:999999999999.99',
            ],
            'method' => [
                'required', 'string',
                'in:cash,card,bank_transfer,mobile_money,insurance,other',
            ],
            'reference' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'paid_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }
}
