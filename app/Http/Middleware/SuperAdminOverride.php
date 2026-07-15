<?php

namespace App\Http\Middleware;

use App\Models\Landlord\User as LandlordUser;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminOverride
{
    /**
     * Enforce landlord superadmin access and enable God-mode authorization.
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {
        $user = $request->user();

        if (! $user) {
            return $this->unauthenticatedResponse();
        }

        if (! $user instanceof LandlordUser) {
            return $this->forbiddenResponse();
        }

        if (
            $user->role !== 'superadmin' ||
            ! (bool) $user->is_active
        ) {
            return $this->forbiddenResponse();
        }

        app()->instance('superadmin.override', true);

        try {
            return $next($request);
        } finally {
            /*
             * Prevent the override leaking between requests when using
             * long-running workers such as Laravel Octane.
             */
            app()->forgetInstance('superadmin.override');
        }
    }

    private function unauthenticatedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], Response::HTTP_UNAUTHORIZED);
    }

    private function forbiddenResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Access denied. Landlord superadmin privileges are required.',
        ], Response::HTTP_FORBIDDEN);
    }
}