<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Authentication\TenantSwitcher;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetTenantContext
{
    public function __construct(
        protected TenantSwitcher $tenantSwitcher,
    ) {
    }

    /**
     * Resolve and activate the tenant before Sanctum authenticates the token.
     *
     * Modes:
     * - optional: landlord requests may continue without a tenant domain.
     * - required: the request must contain a valid tenant domain.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $mode = 'optional'
    ): Response {
        $domain = $this->resolveDomain($request);

        if ($domain === null) {
            if ($mode === 'required') {
                return $this->tenantDomainRequiredResponse();
            }

            return $next($request);
        }

        $tenant = Tenant::query()
            ->where('domain', $domain)
            ->first();

        if (! $tenant) {
            return response()->json([
                'message' => 'Tenant not found.',
                'errors' => [
                    'tenant' => [
                        'The supplied laboratory domain is invalid.',
                    ],
                ],
            ], 404);
        }

        if (! $tenant->is_active) {
            return response()->json([
                'message' => 'This laboratory account is inactive.',
                'errors' => [
                    'tenant' => [
                        'This laboratory account has been suspended or deactivated.',
                    ],
                ],
            ], 403);
        }

        $this->tenantSwitcher->switch($tenant);

        $request->attributes->set('tenant', $tenant);

        try {
            return $next($request);
        } finally {
            $this->tenantSwitcher->restore();
        }
    }

    /**
     * Resolve the tenant domain from the request.
     */
    private function resolveDomain(Request $request): ?string
    {
        $domain = $request->header('X-Tenant-Domain');

        if (blank($domain)) {
            $domain = $request->input('domain');
        }

        if (blank($domain)) {
            $domain = $request->route('domain');
        }

        if (blank($domain)) {
            return null;
        }

        return strtolower(trim((string) $domain));
    }

    /**
     * Return a consistent response when tenant context is mandatory.
     */
    private function tenantDomainRequiredResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Tenant domain is required.',
            'errors' => [
                'tenant' => [
                    'Provide the X-Tenant-Domain request header.',
                ],
            ],
        ], 422);
    }
}