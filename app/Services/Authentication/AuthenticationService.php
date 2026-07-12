<?php

namespace App\Services\Authentication;

use App\Models\Landlord\User as LandlordUser;
use App\Models\PersonalAccessToken;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthenticationService
{
    /**
     * Create a Sanctum personal access token.
     *
     * The custom PersonalAccessToken model automatically binds tenant
     * tokens to the currently active tenant.
     */
    public function createToken(
        Authenticatable $user,
        string $name = 'auth_token',
        array $abilities = ['*']
    ): string {
        return $user
            ->createToken($name, $abilities)
            ->plainTextToken;
    }

    /**
     * Revoke the token used for the current request.
     */
    public function revokeCurrentToken(Request $request): bool
    {
        $user = $request->user();

        if (
            ! $user ||
            ! method_exists($user, 'currentAccessToken')
        ) {
            return false;
        }

        $token = $user->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        $tokenId = $token->getKey();
        $tenantId = $token->tenant_id;
        $userId = $user->getAuthIdentifier();

        $token->delete();

        /*
         * Remove the deleted token from the in-memory user model.
         */
        if (method_exists($user, 'withAccessToken')) {
            $user->withAccessToken(null);
        }

        Log::info('Access token revoked.', [
            'token_id' => $tokenId,
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'user_type' => $user::class,
        ]);

        return true;
    }

    /**
     * Revoke all tokens belonging to a user.
     *
     * The PersonalAccessToken global scope ensures tenant users can revoke
     * only tokens belonging to their currently active tenant.
     */
    public function revokeAllTokens(
        Authenticatable $user
    ): void {
        if (! method_exists($user, 'tokens')) {
            return;
        }

        $user->tokens()->delete();

        Log::info('All access tokens revoked.', [
            'user_id' => $user->getAuthIdentifier(),
            'user_type' => $user::class,
        ]);
    }

    /**
     * Get the authenticated user.
     */
    public function user(
        Request $request
    ): ?Authenticatable {
        return $request->user();
    }

    /**
     * Determine whether the request is authenticated.
     */
    public function check(Request $request): bool
    {
        return $this->user($request) !== null;
    }

    /**
     * Determine the authentication context from the user model.
     */
    public function guard(Request $request): ?string
    {
        $user = $request->user();

        if ($user instanceof LandlordUser) {
            return 'landlord';
        }

        if ($user instanceof TenantUser) {
            return 'tenant';
        }

        return null;
    }

    /**
     * Determine whether the authenticated user is an active superadmin.
     */
    public function isSuperAdmin(Request $request): bool
    {
        $user = $request->user();

        return $user instanceof LandlordUser
            && $user->role === 'superadmin'
            && (bool) $user->is_active;
    }
}