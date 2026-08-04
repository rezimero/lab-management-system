<?php

namespace App\Http\Requests\Referral;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class AcknowledgeLaboratoryReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::DISPATCH_REFERRALS
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'external_reference' => $this->filled('external_reference')
                ? trim((string) $this->input('external_reference'))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'external_reference' => [
                'nullable',
                'string',
                'max:160',
            ],
        ];
    }
}
