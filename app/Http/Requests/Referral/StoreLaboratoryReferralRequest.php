<?php

namespace App\Http\Requests\Referral;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class StoreLaboratoryReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::CREATE_REFERRALS
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'external_reference' => $this->filled('external_reference')
                ? trim((string) $this->input('external_reference'))
                : null,
            'dispatch_notes' => $this->filled('dispatch_notes')
                ? trim((string) $this->input('dispatch_notes'))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'order_item_uuid' => ['required', 'uuid'],
            'referral_laboratory_test_uuid' => ['required', 'uuid'],
            'specimen_uuids' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],
            'specimen_uuids.*' => [
                'required',
                'uuid',
                'distinct',
            ],
            'quoted_cost' => [
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:999999999999.99',
            ],
            'external_reference' => [
                'nullable',
                'string',
                'max:160',
            ],
            'dispatch_notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ];
    }
}
