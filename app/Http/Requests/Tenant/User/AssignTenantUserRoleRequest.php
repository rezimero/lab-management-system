<?php

namespace App\Http\Requests\Tenant\User;

use App\Support\Tenant\TenantUserRoleCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTenantUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'role' => [
                'required',
                'string',
                Rule::in(TenantUserRoleCatalog::keys()),
            ],
        ];
    }
}