<?php

namespace App\Http\Requests\LaboratoryReport;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLaboratoryReportRequest extends FormRequest
{
    /**
     * Determine whether the user may view reports.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::
                    VIEW_LABORATORY_REPORTS
            );
    }

    /**
     * Normalize report-query parameters.
     */
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach ([
            'search',
            'order_uuid',
            'from_date',
            'to_date',
            'sort',
            'direction',
        ] as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = $this->input($field);

            $normalized[$field] = is_string($value)
                ? trim($value)
                : $value;
        }

        $this->merge($normalized);
    }

    /**
     * Validate report-list filters.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'order_uuid' => [
                'nullable',
                'uuid',
            ],

            'patient_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'from_date' => [
                'nullable',
                'date',
            ],

            'to_date' => [
                'nullable',
                'date',
                'after_or_equal:from_date',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'between:1,100',
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'generated_at',
                    'created_at',
                    'report_number',
                ]),
            ],

            'direction' => [
                'nullable',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],
        ];
    }
}
