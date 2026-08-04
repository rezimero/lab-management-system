<?php

namespace App\Services\Tenant\Referral;

use App\Models\Tenant\User as TenantUser;

class ReferralActorSnapshotFactory
{
    /**
     * @return array{id: int, name: string, email: string, role: string}
     */
    public function make(TenantUser $actor): array
    {
        return [
            'id' => (int) $actor->getKey(),
            'name' => (string) $actor->name,
            'email' => (string) $actor->email,
            'role' => (string) $actor->role,
        ];
    }
}
