<?php

namespace Tests\Feature\Landlord;

use Tests\Support\Landlord\DashboardTestCase;

class DashboardOverviewTest extends DashboardTestCase
{
    public function test_superadmin_can_view_dashboard_overview(): void
    {
        $activeTenant = $this->registerLandlordTestTenant();
        $suspendedTenant = $this->registerLandlordTestTenant();

        $suspendedTenant['tenant']->forceFill([
            'is_active' => false,
        ])->save();

        $response = $this->withHeaders(
            $this->superadminHeaders()
        )->getJson('/api/landlord/dashboard/overview');

        $response
            ->assertOk()
            ->assertJsonPath('tenants.total', 2)
            ->assertJsonPath('tenants.active', 1)
            ->assertJsonPath('tenants.suspended', 1)
            ->assertJsonPath('users.total', 2)
            ->assertJsonPath('users.by_role.admin', 2)
            ->assertJsonPath('patients.total', 0)
            ->assertJsonPath('lab_tests.total', 0)
            ->assertJsonPath('test_results.total', 0)
            ->assertJsonStructure([
                'tenants' => [
                    'total',
                    'active',
                    'suspended',
                ],
                'users' => [
                    'total',
                    'by_role',
                ],
                'patients' => [
                    'total',
                ],
                'lab_tests' => [
                    'total',
                ],
                'test_results' => [
                    'total',
                ],
            ]);

        $this->assertTrue(
            (bool) $activeTenant['tenant']->is_active
        );

        $this->assertFalse(
            (bool) $suspendedTenant['tenant']->fresh()->is_active
        );

        $this->assertSame(
            'landlord',
            config('database.default')
        );
    }
}