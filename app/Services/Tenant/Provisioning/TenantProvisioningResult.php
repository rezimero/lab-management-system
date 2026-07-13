<?php

namespace App\Services\Tenant\Provisioning;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;

final class TenantProvisioningResult
{
    public function __construct(
        public readonly Tenant $tenant,
        public readonly TenantUser $administrator,
        public readonly mixed $contextResult = null,
    ) {
    }
}