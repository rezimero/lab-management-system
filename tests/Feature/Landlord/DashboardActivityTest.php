<?php

namespace Tests\Feature\Landlord;

use Tests\Support\Landlord\DashboardTestCase;

class DashboardActivityTest extends DashboardTestCase
{
    public function test_superadmin_can_view_recent_tenant_activity(): void
    {
        $tenantA = $this->registerLandlordTestTenant();
        $tenantB = $this->registerLandlordTestTenant();

        $response = $this->withHeaders(
            $this->superadminHeaders()
        )->getJson(
            '/api/landlord/dashboard/activity?limit=1'
        );

        $response
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonCount(1, 'activities')
            ->assertJsonStructure([
                'activities' => [
                    '*' => [
                        'type',
                        'tenant',
                        'user',
                        'email',
                        'role',
                        'created_at',
                    ],
                ],
                'total',
            ]);

        $activity = $response->json('activities.0');

        $this->assertSame(
            'user_created',
            $activity['type']
        );

        $this->assertSame(
            'admin',
            $activity['role']
        );

        $this->assertContains(
            $activity['tenant'],
            [
                $tenantA['tenant']->name,
                $tenantB['tenant']->name,
            ]
        );

        $this->assertStringEndsWith(
            '@example.test',
            $activity['email']
        );

        $this->assertArrayNotHasKey(
            'password',
            $activity
        );

        $this->assertSame(
            'landlord',
            config('database.default')
        );
    }

    public function test_activity_endpoint_respects_the_requested_limit(): void
    {
        $this->registerLandlordTestTenant();
        $this->registerLandlordTestTenant();

        $response = $this->withHeaders(
            $this->superadminHeaders()
        )->getJson(
            '/api/landlord/dashboard/activity?limit=2'
        );

        $response
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonCount(2, 'activities');
    }
}