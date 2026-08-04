<?php

namespace App\Http\Controllers\Api\Tenant\Concerns;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\EntitlementService;
use App\Support\Commercial\CommercialFeatureCatalog;
use Illuminate\Http\Request;

trait AuthorizesReferrals
{
    protected function ensureReferralEntitlement(
        EntitlementService $entitlements
    ): void {
        $entitlements->ensure(
            $this->referralTenant(),
            CommercialFeatureCatalog::REFERRAL_LABS
        );
    }

    protected function referralUser(Request $request): TenantUser
    {
        $user = $request->user();

        return $user instanceof TenantUser
            ? $user
            : abort(401, 'Unauthenticated.');
    }

    protected function referralTenant(): Tenant
    {
        return Tenant::current()
            ?? abort(422, 'Tenant context is required.');
    }
}
