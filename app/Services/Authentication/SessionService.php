<?php

namespace App\Services\Authentication;

use App\Models\Landlord\User as LandlordUser;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;

class SessionService
{
    /**
     * Store tenant authentication context in the session.
     *
     * Authentication itself remains bearer-token based. These values are
     * supplementary context and must never independently grant access.
     */
    public function startTenantSession(
        Tenant $tenant,
        TenantUser $user
    ): void {
        $this->clear();

        session([
            'tenant_id' => $tenant->id,
            'tenant_domain' => $tenant->domain,
            'tenant_user_id' => $user->id,
            'tenant_user_email' => $user->email,
            'tenant_user_role' => $user->role,
            'is_superadmin' => false,
        ]);
    }

    /**
     * Store landlord authentication context in the session.
     */
    public function startLandlordSession(
        LandlordUser $user
    ): void {
        $this->clear();

        session([
            'landlord_user_id' => $user->id,
            'landlord_user_email' => $user->email,
            'is_superadmin' => (
                $user->role === 'superadmin' &&
                (bool) $user->is_active
            ),
        ]);
    }

    /**
     * Clear authentication-related session values.
     */
    public function clear(): void
    {
        session()->forget([
            'tenant_id',
            'tenant_domain',
            'tenant_user_id',
            'tenant_user_email',
            'tenant_user_role',
            'landlord_user_id',
            'landlord_user_email',
            'is_superadmin',
            'impersonating',
            'impersonated_tenant_id',
            'impersonated_tenant_domain',
        ]);
    }

    /**
     * Completely invalidate the authentication session during logout.
     */
    public function destroy(): void
    {
        $this->clear();

        session()->invalidate();
        session()->regenerateToken();
    }

    /**
     * Determine whether session metadata indicates superadmin context.
     *
     * Authorization must still validate the authenticated landlord user.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) session(
            'is_superadmin',
            false
        );
    }

    /**
     * Get the current tenant ID.
     */
    public function tenantId(): ?int
    {
        $tenantId = session('tenant_id');

        return is_numeric($tenantId)
            ? (int) $tenantId
            : null;
    }

    /**
     * Get the current tenant domain.
     */
    public function tenantDomain(): ?string
    {
        $domain = session('tenant_domain');

        return is_string($domain)
            ? $domain
            : null;
    }

    /**
     * Get the current tenant user ID.
     */
    public function tenantUserId(): ?int
    {
        $userId = session('tenant_user_id');

        return is_numeric($userId)
            ? (int) $userId
            : null;
    }

    /**
     * Get the current landlord user ID.
     */
    public function landlordUserId(): ?int
    {
        $userId = session('landlord_user_id');

        return is_numeric($userId)
            ? (int) $userId
            : null;
    }
}