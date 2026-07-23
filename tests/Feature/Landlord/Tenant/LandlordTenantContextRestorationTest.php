<?php

namespace Tests\Feature\Landlord\Tenant;

use App\Models\Tenant;
use App\Services\Authentication\TenantSwitcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\Support\Landlord\TenantManagementTestCase;

class LandlordTenantContextRestorationTest extends TenantManagementTestCase
{
    public function test_tenant_statistics_restore_the_landlord_context(): void
    {
        $registration = $this->registerLandlordTestTenant();
        $tenant = $registration['tenant'];

        $this->withHeaders(
            $this->superadminHeaders()
        )
            ->getJson(
                '/api/landlord/tenants/'
                .$tenant->id
                .'/stats'
            )
            ->assertOk();

        $this->assertLandlordContext();
    }

    public function test_missing_admin_does_not_leak_the_tenant_context(): void
    {
        $registration = $this->registerLandlordTestTenant();
        $tenant = $registration['tenant'];

        $this->withHeaders(
            $this->superadminHeaders()
        )
            ->postJson(
                '/api/landlord/tenants/'
                .$tenant->id
                .'/reset-admin-password',
                [
                    'email' => 'missing-user@example.test',
                    'new_password' => 'Password123!',
                ]
            )
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'User not found in tenant'
            );

        $this->assertLandlordContext();
    }

    public function test_tenant_switcher_restores_the_landlord_auth_guard(): void
    {
        $registration = $this->registerLandlordTestTenant();
        $tenant = $registration['tenant'];

        app(TenantSwitcher::class)->switch($tenant);

        $this->assertSame(
            'tenant',
            config('auth.defaults.guard')
        );

        $this->assertSame(
            'tenant',
            Auth::getDefaultDriver()
        );

        app(TenantSwitcher::class)->restore();

        $this->assertSame(
            'landlord',
            config('auth.defaults.guard')
        );

        $this->assertSame(
            'landlord',
            Auth::getDefaultDriver()
        );

        $this->assertLandlordContext();
    }

    private function assertLandlordContext(): void
    {
        $this->assertSame(
            'landlord',
            config('database.default')
        );

        $this->assertNull(Tenant::current());

        $this->assertSame(
            'lab_saas_landlord_test',
            DB::connection('landlord')
                ->getDatabaseName()
        );

        $this->assertSame(
            'lab_saas_landlord_test',
            DB::connection()
                ->getDatabaseName()
        );
    }
}
