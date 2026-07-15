<?php

namespace Tests\Feature\Landlord\Tenant;

use Tests\Support\Landlord\TenantManagementTestCase;

class LandlordTenantReadTest extends TenantManagementTestCase
{
    public function test_superadmin_can_list_show_and_read_tenant_stats(): void
    {
        $registration = $this->registerLandlordTestTenant();
        $tenant = $registration['tenant'];

        $headers = $this->superadminHeaders();

        $indexResponse = $this
            ->withHeaders($headers)
            ->getJson('/api/landlord/tenants');

        $indexResponse->assertOk();

        $this->assertGreaterThanOrEqual(
            1,
            (int) $indexResponse->json('total')
        );

        $tenantRow = collect(
            $indexResponse->json('tenants')
        )->firstWhere('id', $tenant->id);

        $this->assertIsArray($tenantRow);
        $this->assertSame(
            $tenant->domain,
            $tenantRow['domain']
        );

        $this->assertArrayHasKey('stats', $tenantRow);
        $this->assertGreaterThanOrEqual(
            1,
            (int) $tenantRow['stats']['users']
        );

        /*
         * The legacy controller leaks Tenant::current().
         * Restore explicitly between requests until the controller is
         * replaced and a dedicated regression test is introduced.
         */
        $this->restoreLandlordContext();

        $showResponse = $this
            ->withHeaders($headers)
            ->getJson(
                '/api/landlord/tenants/'.$tenant->id
            );

        $showResponse
            ->assertOk()
            ->assertJsonPath('id', $tenant->id)
            ->assertJsonPath(
                'name',
                $tenant->name
            )
            ->assertJsonPath(
                'domain',
                $tenant->domain
            );

        $showStats = $showResponse->json('stats');

        $this->assertIsArray($showStats);
        $this->assertArrayHasKey('users', $showStats);
        $this->assertArrayHasKey('patients', $showStats);
        $this->assertArrayHasKey('lab_tests', $showStats);
        $this->assertArrayHasKey(
            'test_results',
            $showStats
        );

        $this->restoreLandlordContext();

        $statsResponse = $this
            ->withHeaders($headers)
            ->getJson(
                '/api/landlord/tenants/'
                .$tenant->id
                .'/stats'
            );

        $statsResponse
            ->assertOk()
            ->assertJsonPath(
                'tenant',
                $tenant->name
            );

        $stats = $statsResponse->json('stats');

        $this->assertIsArray($stats);
        $this->assertGreaterThanOrEqual(
            1,
            (int) $stats['users']
        );
        $this->assertSame(0, (int) $stats['patients']);
        $this->assertSame(0, (int) $stats['lab_tests']);
        $this->assertSame(
            0,
            (int) $stats['test_results']
        );
    }

    public function test_missing_tenant_returns_not_found(): void
    {
        $headers = $this->superadminHeaders();

        $this->withHeaders($headers)
            ->getJson('/api/landlord/tenants/999999999')
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'Tenant not found'
            );

        $this->withHeaders($headers)
            ->getJson(
                '/api/landlord/tenants/999999999/stats'
            )
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'Tenant not found'
            );
    }
}