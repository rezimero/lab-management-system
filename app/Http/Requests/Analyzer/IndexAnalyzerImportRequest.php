<?php

namespace App\Http\Requests\Analyzer;

use App\Models\AnalyzerResultImport;
use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAnalyzerImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::VIEW_ANALYZER_IMPORTS
            );
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach ([
            'search',
            'status',
            'analyzer_uuid',
            'sample_id',
        ] as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = trim((string) $this->input($field));
            $payload[$field] = $value === '' ? null : $value;
        }

        $this->merge($payload);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:191'],
            'status' => [
                'nullable',
                Rule::in([
                    AnalyzerResultImport::STATUS_RECEIVED,
                    AnalyzerResultImport::STATUS_NEEDS_REVIEW,
                    AnalyzerResultImport::STATUS_READY,
                    AnalyzerResultImport::STATUS_IMPORTED,
                    AnalyzerResultImport::STATUS_REJECTED,
                    AnalyzerResultImport::STATUS_FAILED,
                    AnalyzerResultImport::STATUS_DUPLICATE,
                ]),
            ],
            'analyzer_uuid' => ['nullable', 'uuid'],
            'sample_id' => ['nullable', 'string', 'max:191'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
