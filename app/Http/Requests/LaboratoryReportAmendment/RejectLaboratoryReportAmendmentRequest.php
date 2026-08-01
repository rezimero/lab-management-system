<?php

namespace App\Http\Requests\LaboratoryReportAmendment;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class RejectLaboratoryReportAmendmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::APPROVE_LABORATORY_REPORT_AMENDMENTS
            );
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('rejection_reason')) {
            return;
        }

        $this->merge([
            'rejection_reason' => trim(
                (string) $this->input(
                    'rejection_reason'
                )
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => [
                'required',
                'string',
                'min:10',
                'max:5000',
                'regex:/[\\pL\\pN]{3,}/u',
            ],
        ];
    }
}
