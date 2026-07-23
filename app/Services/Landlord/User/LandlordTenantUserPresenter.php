<?php

namespace App\Services\Landlord\User;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;

class LandlordTenantUserPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(
        Tenant $tenant,
        TenantUser $user
    ): array {
        return [
            'user_key' => sprintf(
                '%d:%d',
                $tenant->getKey(),
                $user->getKey()
            ),
            'id' => (int) $user->getKey(),
            'tenant_id' => (int) $tenant->getKey(),
            'tenant_name' => $tenant->name,
            'tenant_domain' => $tenant->domain,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => (bool) $user->is_active,
            'email_verified' =>
                $user->email_verified_at !== null,
            'email_verified_at' =>
                $user->email_verified_at?->toISOString(),
            'created_at' =>
                $user->created_at?->toISOString(),
            'updated_at' =>
                $user->updated_at?->toISOString(),
        ];
    }
}
