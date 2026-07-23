<?php

namespace Tests\Feature\Landlord;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\DB;
use Tests\Support\Landlord\TenantManagementTestCase;

class LandlordImpersonationFailureRestorationTest extends
    TenantManagementTestCase
{
    public function test_tenant_without_users_returns_not_found_and_restores_landlord_context(): void
    {
        $registration = $this->registerLandlordTestTenant();
        $tenant = $registration['tenant'];

        $this->runInTenant(
            $tenant,
            static function (): void {
                TenantUser::query()->delete();
            }
        );

        $this
            ->withHeaders($this->superadminHeaders())
            ->postJson(
                '/api/landlord/impersonate/'
                .$tenant->getKey()
            )
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'No users found in this tenant'
            )
            ->assertSessionMissing(
                'impersonating'
            )
            ->assertSessionMissing(
                'impersonated_tenant_id'
            )
            ->assertSessionMissing(
                'impersonated_tenant_name'
            )
            ->assertSessionMissing(
                'impersonated_user_id'
            )
            ->assertSessionMissing(
                'impersonated_user_email'
            );

        $this->assertSame(
            'landlord',
            config('database.default')
        );
        $this->assertNotSame(
            'tenant',
            config('auth.defaults.guard')
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
