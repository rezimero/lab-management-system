<?php

namespace App\Http\Requests\Referral;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class StoreReferralLaboratoryTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::MANAGE_REFERRAL_LABORATORIES
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'external_test_code' => strtoupper(trim(
                (string) $this->input('external_test_code')
            )),
            'external_test_name' => trim(
                (string) $this->input('external_test_name')
            ),
            'currency' => strtoupper(trim(
                (string) ($this->input('currency') ?: 'NGN')
            )),
        ]);
    }

    public function rules(): array
    {
        return [
            'lab_test_uuid' => [
                'required',
                'uuid',
                'exists:tenant.lab_tests,uuid',
            ],
            'external_test_code' => [
                'required',
                'string',
                'max:120',
            ],
            'external_test_name' => [
                'required',
                'string',
                'max:180',
            ],
            'cost' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
                'max:999999999999.99',
            ],
            'currency' => [
                'required',
                'string',
                'size:3',
                'alpha',
            ],
            'turnaround_hours' => [
                'nullable',
                'integer',
                'min:1',
                'max:87600',
            ],
            'specimen_requirements' => [
                'nullable',
                'array',
            ],
            'specimen_requirements.specimen_type' => [
                'nullable',
                'string',
                'max:120',
            ],
            'specimen_requirements.container' => [
                'nullable',
                'string',
                'max:120',
            ],
            'specimen_requirements.minimum_volume' => [
                'nullable',
                'string',
                'max:120',
            ],
            'specimen_requirements.instructions' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
