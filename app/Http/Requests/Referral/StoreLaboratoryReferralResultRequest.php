<?php

namespace App\Http\Requests\Referral;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLaboratoryReferralResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::CAPTURE_REFERRAL_RESULTS
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'external_report_number' => $this->filled(
                'external_report_number'
            )
                ? trim((string) $this->input('external_report_number'))
                : null,
            'notes' => $this->filled('notes')
                ? trim((string) $this->input('notes'))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'external_report_number' => [
                'nullable',
                'string',
                'max:160',
            ],
            'results_data' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],
            'results_data.*' => ['array'],
            'results_data.*.name' => [
                'required',
                'string',
                'max:255',
            ],
            'results_data.*.value' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'results_data.*.unit' => [
                'nullable',
                'string',
                'max:100',
            ],
            'results_data.*.reference_range' => [
                'nullable',
                'string',
                'max:255',
            ],
            'results_data.*.flag' => [
                'nullable',
                Rule::in([
                    'normal',
                    'low',
                    'high',
                    'critical',
                    'abnormal',
                ]),
            ],
            'results_data.*.notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'reported_at' => [
                'nullable',
                'date',
            ],
            'final_cost' => [
                'nullable',
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:999999999999.99',
            ],
        ];
    }
}
