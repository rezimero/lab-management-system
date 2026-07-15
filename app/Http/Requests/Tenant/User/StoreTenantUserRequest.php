<?php

namespace App\Http\Requests\Tenant\User;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserRoleCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantUserRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(TenantUser::class, 'email'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
            ],
            'role' => [
                'required',
                'string',
                Rule::in(TenantUserRoleCatalog::keys()),
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}