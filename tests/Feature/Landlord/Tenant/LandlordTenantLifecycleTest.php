<?php

namespace Tests\Feature\Landlord\Tenant;

use App\Models\Tenant;
use Tests\Support\Landlord\TenantManagementTestCase;

class LandlordTenantLifecycleTest extends TenantManagementTestCase
{
    public function test_superadmin_can_update_suspend_and_activate_a_tenant(): void
    {
        $registration = $this->registerLandlordTestTenant();
        $tenant = $registration['tenant'];

        $headers = $this->superadminHeaders();

        $updatedName = 'Updated Landlord Test Laboratory';

        $this->withHeaders($headers)
            ->putJson(
                '/api/landlord/tenants/'.$tenant->id,
                [
                    'name' => $updatedName,
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Tenant updated successfully'
            )
            ->assertJsonPath(
                'tenant.name',
                $updatedName
            );

        $this->restoreLandlordContext();

        $this->assertSame(
            $updatedName,
            Tenant::query()
                ->findOrFail($tenant->id)
                ->name
        );

        $this->withHeaders($headers)
            ->postJson(
                '/api/landlord/tenants/'
                .$tenant->id
                .'/suspend'
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Tenant suspended successfully'
            )
            ->assertJsonPath(
                'tenant.is_active',
                false
            );

        $this->restoreLandlordContext();

        $this->assertFalse(
            (bool) Tenant::query()
                ->findOrFail($tenant->id)
                ->is_active
        );

        $this->withHeaders($headers)
            ->postJson(
                '/api/landlord/tenants/'
                .$tenant->id
                .'/activate'
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Tenant activated successfully'
            )
            ->assertJsonPath(
                'tenant.is_active',
                true
            );

        $this->restoreLandlordContext();

        $this->assertTrue(
            (bool) Tenant::query()
                ->findOrFail($tenant->id)
                ->is_active
        );
    }
}