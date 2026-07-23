<?php

namespace App\Services\Tenant\User;

use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Http\Request;

class TenantUserSessionService
{
    public function revokeAll(
        Tenant $tenant,
        TenantUser $user
    ): void {
        $this->tokenQuery(
            tenant: $tenant,
            user: $user
        )->delete();
    }

    public function revokeOthers(
        Tenant $tenant,
        TenantUser $user,
        ?int $currentTokenId
    ): void {
        $query = $this->tokenQuery(
            tenant: $tenant,
            user: $user
        );

        if ($currentTokenId !== null) {
            $query->where(
                'id',
                '!=',
                $currentTokenId
            );
        }

        $query->delete();
    }

    public function currentTokenId(
        Request $request
    ): ?int {
        $token = $request
            ->user()
            ?->currentAccessToken();

        if (
            ! is_object($token)
            || ! method_exists(
                $token,
                'getKey'
            )
        ) {
            return null;
        }

        $tokenId = $token->getKey();

        return is_numeric($tokenId)
            ? (int) $tokenId
            : null;
    }

    private function tokenQuery(
        Tenant $tenant,
        TenantUser $user
    ) {
        return PersonalAccessToken::query()
            ->where(
                'tokenable_type',
                $user->getMorphClass()
            )
            ->where(
                'tokenable_id',
                $user->getKey()
            )
            ->where(
                'tenant_id',
                $tenant->getKey()
            );
    }
}