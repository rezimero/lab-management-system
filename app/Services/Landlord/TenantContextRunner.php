<?php

namespace App\Services\Landlord;

use App\Models\Tenant;
use App\Services\Authentication\TenantSwitcher;
use Closure;

class TenantContextRunner
{
    public function __construct(
        protected TenantSwitcher $tenantSwitcher,
    ) {
    }

    /**
     * Execute an operation inside a tenant database context.
     *
     * The landlord connection is always restored, including when the
     * tenant operation throws an exception.
     */
    public function run(
        Tenant $tenant,
        Closure $operation
    ): mixed {
        try {
            $this->tenantSwitcher->switch($tenant);

            return $operation();
        } finally {
            $this->tenantSwitcher->restore();
        }
    }
}