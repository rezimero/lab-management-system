<?php

namespace Tests\Feature\Landlord;

use Illuminate\Support\Facades\DB;
use Tests\Support\Landlord\DashboardTestCase;

class DashboardStatsTest extends DashboardTestCase
{
    public function test_superadmin_can_view_platform_statistics(): void
    {
        $tenantA = $this->registerLandlordTestTenant();
        $tenantB = $this->registerLandlordTestTenant();

        $response = $this->withHeaders(
            $this->superadminHeaders()
        )->getJson('/api/landlord/dashboard/stats');

        $response
            ->assertOk()
            ->assertJsonPath('total_tenants', 2)
            ->assertJsonPath('total_users', 2)
            ->assertJsonPath('total_patients', 0)
            ->assertJsonPath('total_lab_tests', 0)
            ->assertJsonPath('total_test_results', 0)
            ->assertJsonCount(2, 'tenants')
            ->assertJsonStructure([
                'total_tenants',
                'total_users',
                'total_patients',
                'total_lab_tests',
                'total_test_results',
                'tenants' => [
                    '*' => [
                        'id',
                        'name',
                        'domain',
                        'is_active',
                        'stats' => [
                            'users',
                            'patients',
                            'lab_tests',
                            'test_results',
                        ],
                    ],
                ],
            ]);

        $tenants = collect(
            $response->json('tenants')
        )->keyBy('domain');

        $this->assertTrue(
            $tenants->has($tenantA['domain'])
        );

        $this->assertTrue(
            $tenants->has($tenantB['domain'])
        );

        $this->assertSame(
            1,
            $tenants->get($tenantA['domain'])['stats']['users']
        );

        $this->assertSame(
            1,
            $tenants->get($tenantB['domain'])['stats']['users']
        );

        $this->assertArrayNotHasKey(
            'database',
            $tenants->get($tenantA['domain'])
        );

        $this->assertSame(
            'landlord',
            config('database.default')
        );

        $this->assertSame(
            'lab_saas_landlord_test',
            DB::connection('landlord')->getDatabaseName()
        );
    }
}