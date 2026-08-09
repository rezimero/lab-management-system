<?php

namespace App\Http\Requests\Referral;

use App\Models\LaboratoryReferral;
use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLaboratoryReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::VIEW_REFERRALS
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->filled('search')
                ? trim((string) $this->input('search'))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:180'],
            'status' => [
                'nullable',
                'string',
                Rule::in([
                    LaboratoryReferral::STATUS_DRAFT,
                    LaboratoryReferral::STATUS_DISPATCHED,
                    LaboratoryReferral::STATUS_ACKNOWLEDGED,
                    LaboratoryReferral::STATUS_RESULT_RECEIVED,
                    LaboratoryReferral::STATUS_COMPLETED,
                    LaboratoryReferral::STATUS_REJECTED,
                    LaboratoryReferral::STATUS_CANCELLED,
                ]),
            ],
            'laboratory_uuid' => ['nullable', 'uuid'],
            'branch_uuid' => ['nullable', 'uuid'],
            'order_item_uuid' => ['nullable', 'uuid'],
            'date_from' => ['nullable', 'date'],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
            'overdue' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
