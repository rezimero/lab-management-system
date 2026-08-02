<?php

namespace App\Http\Requests\Branch;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::MANAGE_BRANCHES
            );
    }

    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach ([
            'code', 'name', 'email', 'phone', 'address_line_1',
            'address_line_2', 'city', 'state', 'country',
            'parent_branch_uuid',
        ] as $field) {
            if ($this->has($field)) {
                $value = trim((string) $this->input($field));
                $payload[$field] = $value === '' ? null : $value;
            }
        }

        if (isset($payload['code'])) {
            $payload['code'] = strtoupper($payload['code']);
        }

        $this->merge($payload);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:40',
                Rule::unique('branches', 'code'),
            ],
            'name' => ['required', 'string', 'max:180'],
            'type' => ['required', 'string', 'in:laboratory,collection_centre'],
            'parent_branch_uuid' => [
                'nullable', 'uuid', Rule::exists('branches', 'uuid'),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'is_head_office' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
