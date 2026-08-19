<?php

namespace App\Http\Controllers\Api\Tenant\Concerns;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\EntitlementService;
use App\Support\Commercial\CommercialFeatureCatalog;
use Illuminate\Http\Request;

trait AuthorizesAnalyzers
{
    protected function ensureAnalyzerEntitlement(
        EntitlementService $entitlements
    ): void {
        $entitlements->ensure(
            $this->analyzerTenant(),
            CommercialFeatureCatalog::ANALYZER_INTEGRATION
        );
    }

    protected function analyzerUser(Request $request): TenantUser
    {
        $user = $request->user();

        return $user instanceof TenantUser
            ? $user
            : abort(401, 'Unauthenticated.');
    }

    protected function analyzerTenant(): Tenant
    {
        return Tenant::current()
            ?? abort(422, 'Tenant context is required.');
    }

    protected function ensureAnalyzerPermission(
        Request $request,
        string $permission
    ): TenantUser {
        $user = $this->analyzerUser($request);

        if (! $user->hasPermission($permission)) {
            abort(403, 'Unauthorized.');
        }

        return $user;
    }
}
