<?php

namespace Tests\Feature\Tenant\Portal;

use Tests\Support\Tenant\PortalAuthenticationTestCase;

class TenantPortalTenantContextTest extends PortalAuthenticationTestCase
{
    public function test_active_tenant_branding_can_be_resolved_for_portal_login(): void
    {
        $tenant = $this->registerTestTenant(
            'portal-context'
        );

        $this->withHeader(
            'X-Tenant-Domain',
            $tenant['domain']
        )
            ->getJson('/api/portal/context')
            ->assertOk()
            ->assertExactJson([
                'domain' => $tenant['domain'],
                'name' => (
                    'Authentication Test Laboratory'
                ),
            ]);
    }

    public function test_portal_context_requires_a_valid_active_tenant(): void
    {
        $this->getJson('/api/portal/context')
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Tenant domain is required.'
            );

        $this->withHeader(
            'X-Tenant-Domain',
            'missing-portal-context'
        )
            ->getJson('/api/portal/context')
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'Tenant not found.'
            );

        $tenant = $this->registerTestTenant(
            'inactive-portal-context'
        );

        $tenant['tenant']->forceFill([
            'is_active' => false,
        ])->save();

        $this->withHeader(
            'X-Tenant-Domain',
            $tenant['domain']
        )
            ->getJson('/api/portal/context')
            ->assertForbidden()
            ->assertJsonPath(
                'message',
                'This laboratory account is inactive.'
            );
    }
}
