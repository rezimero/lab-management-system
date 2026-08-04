<?php

namespace App\Http\Controllers\Api\Tenant\Concerns;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\EntitlementService;
use App\Support\Commercial\CommercialFeatureCatalog;
use Illuminate\Http\Request;

trait AuthorizesInventory
{
    protected function ensureInventoryEntitlement(EntitlementService $entitlements): void
    {
        $entitlements->ensure($this->inventoryTenant(), CommercialFeatureCatalog::INVENTORY);
    }

    protected function inventoryUser(Request $request): TenantUser
    {
        $user = $request->user();

        return $user instanceof TenantUser ? $user : abort(401, 'Unauthenticated.');
    }

    protected function inventoryTenant(): Tenant
    {
        return Tenant::current() ?? abort(422, 'Tenant context is required.');
    }

    protected function ensureInventoryPermission(TenantUser $user, string $permission): void
    {
        if (! $user->hasPermission($permission)) {
            abort(403, 'Unauthorized.');
        }
    }
}
