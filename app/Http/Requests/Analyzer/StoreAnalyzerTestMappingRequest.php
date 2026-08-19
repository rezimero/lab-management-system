<?php

namespace App\Http\Requests\Analyzer;

use App\Models\AnalyzerInstrument;
use App\Models\AnalyzerTestMapping;
use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnalyzerTestMappingRequest extends FormRequest
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
            'lab_test_uuid',
            'instrument_test_code',
            'instrument_test_name',
        ] as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = trim((string) $this->input($field));
            $payload[$field] = $value === '' ? null : $value;
        }

        if (isset($payload['instrument_test_code'])) {
            $payload['instrument_test_code'] = strtoupper(
                $payload['instrument_test_code']
            );
        }

        $this->merge($payload);
    }

    public function rules(): array
    {
        $instrument = $this->route('analyzer');
        $mapping = $this->route('testMapping');

        $instrumentId = $instrument instanceof AnalyzerInstrument
            ? $instrument->getKey()
            : 0;

        $mappingId = $mapping instanceof AnalyzerTestMapping
            ? $mapping->getKey()
            : null;

        return [
            'lab_test_uuid' => [
                'required',
                'uuid',
                Rule::exists('lab_tests', 'uuid'),
            ],
            'instrument_test_code' => [
                'required',
                'string',
                'max:120',
                Rule::unique(
                    'analyzer_test_mappings',
                    'instrument_test_code'
                )
                    ->where(
                        fn ($query) => $query->where(
                            'analyzer_instrument_id',
                            $instrumentId
                        )
                    )
                    ->ignore($mappingId),
            ],
            'instrument_test_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
