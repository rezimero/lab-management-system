<?php

namespace App\Http\Requests\Analyzer;

use App\Models\AnalyzerParameterMapping;
use App\Models\AnalyzerTestMapping;
use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnalyzerParameterMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::MANAGE_ANALYZER_MAPPINGS
            );
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach ([
            'instrument_parameter_code',
            'instrument_parameter_name',
            'target_parameter_name',
            'expected_unit',
        ] as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = trim((string) $this->input($field));
            $payload[$field] = $value === '' ? null : $value;
        }

        if (isset($payload['instrument_parameter_code'])) {
            $payload['instrument_parameter_code'] = strtoupper(
                $payload['instrument_parameter_code']
            );
        }

        $this->merge($payload);
    }

    public function rules(): array
    {
        $testMapping = $this->route('testMapping');
        $parameterMapping = $this->route('parameterMapping');

        $testMappingId = $testMapping instanceof AnalyzerTestMapping
            ? $testMapping->getKey()
            : 0;

        $parameterMappingId = $parameterMapping
            instanceof AnalyzerParameterMapping
            ? $parameterMapping->getKey()
            : null;

        return [
            'instrument_parameter_code' => [
                'required',
                'string',
                'max:120',
                Rule::unique(
                    'analyzer_parameter_mappings',
                    'instrument_parameter_code'
                )
                    ->where(
                        fn ($query) => $query->where(
                            'analyzer_test_mapping_id',
                            $testMappingId
                        )
                    )
                    ->ignore($parameterMappingId),
            ],
            'instrument_parameter_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'target_parameter_name' => [
                'required',
                'string',
                'max:255',
            ],
            'expected_unit' => [
                'nullable',
                'string',
                'max:120',
            ],
            'is_required' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
