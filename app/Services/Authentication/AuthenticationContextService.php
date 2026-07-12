<?php

namespace App\Services\Authentication;

use App\Models\Landlord\User as LandlordUser;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthenticationContextService
{
    public function __construct(
        protected AuthenticationService $authenticationService,
        protected SessionService $sessionService,
    ) {
    }

    /**
     * Revoke the current token and completely clear authentication state.
     */
    public function logout(Request $request): void
    {
        $user = $request->user();

        $tokenRevoked = $this->authenticationService
            ->revokeCurrentToken($request);

        $this->sessionService->destroy();

        /*
         * Prevent the current Request object from retaining its user.
         */
        $request->setUserResolver(
            static fn (): null => null
        );

        /*
         * Laravel's RequestGuard caches the authenticated user. Discard every
         * resolved guard so subsequent requests must authenticate again.
         *
         * This is especially important in feature tests and long-running
         * application workers.
         */
        Auth::forgetGuards();

        Log::info('User logged out.', [
            'user_id' => $user?->getAuthIdentifier(),
            'email' => $user?->email,
            'user_type' => $user ? $user::class : null,
            'token_revoked' => $tokenRevoked,
        ]);
    }

    /**
     * Return the authenticated user and its actual model context.
     */
    public function user(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            abort(response()->json([
                'message' => 'User not authenticated.',
            ], 401));
        }

        if ($user instanceof LandlordUser) {
            $isSuperAdmin = (
                $user->role === 'superadmin' &&
                (bool) $user->is_active
            );

            return [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => (bool) $user->is_active,
                ],
                'tenant' => null,
                'guard' => 'landlord',
                'is_superadmin' => $isSuperAdmin,
            ];
        }

        if (! $user instanceof TenantUser) {
            abort(response()->json([
                'message' => 'Unsupported authentication context.',
            ], 403));
        }

        $tenant = Tenant::current();

        if (! $tenant) {
            abort(response()->json([
                'message' => 'Tenant context is required.',
            ], 422));
        }

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => (bool) $user->is_active,
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
            ],
            'guard' => 'tenant',
            'is_superadmin' => false,
        ];
    }

    /**
     * Return the current tenant context.
     */
    public function tenant(): array
    {
        $user = request()->user();

        if (! $user) {
            abort(response()->json([
                'message' => 'User not authenticated.',
            ], 401));
        }

        if ($user instanceof LandlordUser) {
            return [
                'tenant' => null,
                'is_superadmin' => (
                    $user->role === 'superadmin' &&
                    (bool) $user->is_active
                ),
                'message' => (
                    'Landlord users have no active tenant context.'
                ),
            ];
        }

        if (! $user instanceof TenantUser) {
            abort(response()->json([
                'message' => 'Unsupported authentication context.',
            ], 403));
        }

        $tenant = Tenant::current();

        if (! $tenant) {
            abort(response()->json([
                'message' => 'Tenant not found.',
            ], 404));
        }

        return [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'is_active' => (bool) $tenant->is_active,
                'trial_ends_at' => $tenant->trial_ends_at,
                'subscription_ends_at' => (
                    $tenant->subscription_ends_at
                ),
            ],
            'is_superadmin' => false,
        ];
    }
}