<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Models\Tenant\User as TenantUser;

class LaboratoryReportAmendmentActorSnapshotFactory
{
    /**
     * @return array<string, mixed>
     */
    public function make(TenantUser $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];
    }
}
