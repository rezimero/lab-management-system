<?php

namespace App\Http\Requests\Branch;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignBranchUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::MANAGE_BRANCHES
            );
    }

    public function rules(): array
    {
        return [
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => [
                'required', 'integer', 'distinct', Rule::exists('users', 'id'),
            ],
            'primary_user_id' => [
                'nullable', 'integer', Rule::in($this->input('user_ids', [])),
            ],
        ];
    }
}
