<?php

namespace App\Http\Requests\Branch;

use App\Models\Branch;
use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Validation\Rule;

class UpdateBranchRequest extends StoreBranchRequest
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
        $branch = $this->route('branch');
        $branchId = $branch instanceof Branch
            ? $branch->getKey()
            : null;

        $rules = parent::rules();
        $rules['code'] = [
            'required', 'string', 'max:40',
            Rule::unique('branches', 'code')->ignore($branchId),
        ];

        return $rules;
    }
}
