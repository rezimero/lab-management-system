<?php

namespace App\Http\Requests;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class ViewTenantProfileRequest extends FormRequest
{
    /**
     * Only active tenant administrators may view settings.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::
                    MANAGE_TENANT_PROFILE
            );
    }

    /**
     * Profile retrieval has no request payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}