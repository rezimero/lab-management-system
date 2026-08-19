<?php

namespace App\Http\Requests\Analyzer;

use App\Models\AnalyzerInstrument;
use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnalyzerInstrumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::MANAGE_ANALYZERS
            );
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach ([
            'code',
            'name',
            'manufacturer',
            'model',
            'serial_number',
            'adapter_key',
            'transport_mode',
            'notes',
            'branch_uuid',
        ] as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = trim((string) $this->input($field));
            $payload[$field] = $value === '' ? null : $value;
        }

        if (isset($payload['code'])) {
            $payload['code'] = strtoupper($payload['code']);
        }

        if (isset($payload['adapter_key'])) {
            $payload['adapter_key'] = strtolower($payload['adapter_key']);
        }

        if (isset($payload['transport_mode'])) {
            $payload['transport_mode'] = strtolower(
                $payload['transport_mode']
            );
        }

        $this->merge($payload);
    }

    public function rules(): array
    {
        $instrument = $this->route('analyzer');
        $id = $instrument instanceof AnalyzerInstrument
            ? $instrument->getKey()
            : null;

        return [
            'branch_uuid' => [
                'required',
                'uuid',
                Rule::exists('branches', 'uuid'),
            ],
            'code' => [
                'required',
                'string',
                'max:60',
                Rule::unique('analyzer_instruments', 'code')->ignore($id),
            ],
            'name' => ['required', 'string', 'max:180'],
            'manufacturer' => ['nullable', 'string', 'max:180'],
            'model' => ['nullable', 'string', 'max:180'],
            'serial_number' => ['nullable', 'string', 'max:180'],
            'adapter_key' => [
                'sometimes',
                'required',
                Rule::in([
                    AnalyzerInstrument::ADAPTER_CANONICAL_JSON_V1,
                ]),
            ],
            'transport_mode' => [
                'sometimes',
                'required',
                Rule::in([
                    AnalyzerInstrument::TRANSPORT_GATEWAY,
                    AnalyzerInstrument::TRANSPORT_DIRECT_HTTP,
                ]),
            ],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
