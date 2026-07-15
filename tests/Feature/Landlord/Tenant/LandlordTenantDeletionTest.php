<?php

namespace Tests\Feature\Landlord\Tenant;

use App\Models\Tenant;
use Tests\Support\Landlord\TenantManagementTestCase;

class LandlordTenantDeletionTest extends TenantManagementTestCase
{
    public function test_superadmin_can_delete_a_tenant_and_its_database(): void
    {
        $registration = $this->registerLandlordTestTenant();

        $tenant = $registration['tenant'];
        $database = $tenant->database;

        $this->assertTrue(
            $this->tenantDatabaseExists($database)
        );

        $headers = $this->superadminHeaders();

        $this->withHeaders($headers)
            ->deleteJson(
                '/api/landlord/tenants/'.$tenant->id
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Tenant deleted successfully'
            );

        $this->restoreLandlordContext();

        $this->assertFalse(
            Tenant::query()
                ->whereKey($tenant->id)
                ->exists()
        );

        $this->assertFalse(
            $this->tenantDatabaseExists($database)
        );
    }
}