<?php

namespace App\Http\Requests\Tenant\User;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserRoleCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantUserRequest extends FormRequest
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
                'sometimes',
                'string',
                'max:255',
            ],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique(TenantUser::class, 'email')
                    ->ignore($this->targetUserId()),
            ],
            'password' => [
                'sometimes',
                'string',
                'min:8',
            ],
            'role' => [
                'sometimes',
                'string',
                Rule::in(TenantUserRoleCatalog::keys()),
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    private function targetUserId(): int|string|null
    {
        $user = $this->route('user');

        if ($user instanceof TenantUser) {
            return $user->getKey();
        }

        return is_int($user) || is_string($user)
            ? $user
            : null;
    }
}