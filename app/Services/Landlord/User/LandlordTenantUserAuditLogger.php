<?php

namespace App\Services\Landlord\User;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\Log;

class LandlordTenantUserAuditLogger
{
    /**
     * @param array<int, string> $changedFields
     */
    public function log(
        string $event,
        mixed $actorId,
        Tenant $tenant,
        ?TenantUser $user = null,
        ?int $userId = null,
        array $changedFields = []
    ): void {
        Log::notice(
            'Rezhel landlord user-governance action.',
            [
                'event' => $event,
                'actor_id' => $actorId,
                'tenant_id' => $tenant->getKey(),
                'tenant_domain' => $tenant->domain,
                'tenant_user_id' =>
                    $user?->getKey() ?? $userId,
                'changed_fields' => array_values(
                    array_unique($changedFields)
                ),
            ]
        );
    }
}
