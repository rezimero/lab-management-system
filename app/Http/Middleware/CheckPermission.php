<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Require the supplied permission.
     *
     * Landlord superadmins bypass ordinary permission restrictions.
     */
    public function handle(
        Request $request,
        Closure $next,
        string $permission
    ): Response {
        $user = $request->user();

        if (! $user) {
            return $this->unauthenticatedResponse();
        }

        if ($this->hasSuperAdminOverride()) {
            return $next($request);
        }

        if (
            method_exists($user, 'hasPermissionTo') &&
            $user->hasPermissionTo($permission)
        ) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Access denied. You do not have the required permission.',
            'required_permission' => $permission,
        ], Response::HTTP_FORBIDDEN);
    }

    private function hasSuperAdminOverride(): bool
    {
        return app()->bound('superadmin.override') &&
            app('superadmin.override') === true;
    }

    private function unauthenticatedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], Response::HTTP_UNAUTHORIZED);
    }
}