<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Require one of the supplied roles.
     *
     * Landlord superadmins bypass ordinary role restrictions.
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if (! $user) {
            return $this->unauthenticatedResponse();
        }

        if ($this->hasSuperAdminOverride()) {
            return $next($request);
        }

        $roles = array_values(
            array_filter(
                array_map(
                    static fn (string $role): string => strtolower(trim($role)),
                    $roles
                )
            )
        );

        $userRole = strtolower(trim((string) $user->role));

        if (in_array($userRole, $roles, true)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Access denied. You do not have the required role.',
            'required_roles' => $roles,
            'your_role' => $userRole,
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