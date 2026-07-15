<?php

namespace App\Services\Landlord\Tenant;

use App\Models\Tenant;
use App\Services\Tenant\Provisioning\TenantProvisioner;

class LandlordTenantProvisioningService
{
    public function __construct(
        protected TenantProvisioner $tenantProvisioner,
    ) {
    }

    /**
     * Provision a tenant through the landlord administration API.
     *
     * @param array{
     *     name: string,
     *     domain: string,
     *     admin_name: string,
     *     admin_email: string,
     *     admin_password: string
     * } $data
     */
    public function provision(array $data): Tenant
    {
        return $this->tenantProvisioner
            ->provision([
                'tenant_name' => $data['name'],
                'tenant_domain' => $data['domain'],
                'admin_name' => $data['admin_name'],
                'admin_email' => $data['admin_email'],
                'admin_password' => (
                    $data['admin_password']
                ),
            ])
            ->tenant;
    }
}