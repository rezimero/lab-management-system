<?php

namespace Tests\Feature\Landlord;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\DB;
use Tests\Support\Landlord\TenantManagementTestCase;

class LandlordImpersonationContractTest extends
    TenantManagementTestCase
{
    public function test_superadmin_can_impersonate_an_active_tenant_with_a_tenant_bound_token(): void
    {
        $registration = $this->registerLandlordTestTenant();
        $tenant = $registration['tenant'];

        $administrator = $this->runInTenant(
            $tenant,
            static fn (): TenantUser => TenantUser::query()
                ->where('role', 'admin')
                ->firstOrFail()
        );

        $response = $this
            ->withHeaders($this->superadminHeaders())
            ->postJson(
                '/api/landlord/impersonate/'
                .$tenant->getKey()
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Impersonating tenant: '.$tenant->name
            )
            ->assertJsonPath(
                'tenant.id',
                $tenant->getKey()
            )
            ->assertJsonPath(
                'tenant.name',
                $tenant->name
            )
            ->assertJsonPath(
                'tenant.domain',
                $tenant->domain
            )
            ->assertJsonPath(
                'user.id',
                $administrator->getKey()
            )
            ->assertJsonPath(
                'user.name',
                $administrator->name
            )
            ->assertJsonPath(
                'user.email',
                $administrator->email
            )
            ->assertJsonPath(
                'user.role',
                'admin'
            )
            ->assertJsonPath(
                'is_impersonating',
                true
            );

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $this->assertTrue(
            (bool) session('impersonating')
        );
        $this->assertSame(
            $tenant->getKey(),
            session('impersonated_tenant_id')
        );
        $this->assertSame(
            $tenant->name,
            session('impersonated_tenant_name')
        );
        $this->assertSame(
            $administrator->getKey(),
            session('impersonated_user_id')
        );
        $this->assertSame(
            $administrator->email,
            session('impersonated_user_email')
        );

        $this->assertDatabaseHas(
            'personal_access_tokens',
            [
                'tenant_id' => $tenant->getKey(),
                'tokenable_type' => (
                    $administrator->getMorphClass()
                ),
                'tokenable_id' => (
                    $administrator->getKey()
                ),
                'name' => 'impersonation_token',
            ],
            'landlord'
        );

        $this->assertLandlordContext();

        $this
            ->withHeaders(
                $this->tenantHeaders(
                    $token,
                    $tenant->domain
                )
            )
            ->getJson('/api/departments')
            ->assertOk();

        $this->assertLandlordContext();
    }

    public function test_status_and_stop_contracts_are_preserved(): void
    {
        $headers = $this->superadminHeaders();

        $this
            ->withHeaders($headers)
            ->getJson('/api/landlord/impersonate/status')
            ->assertOk()
            ->assertJsonPath(
                'is_impersonating',
                false
            )
            ->assertJsonPath('tenant', null)
            ->assertJsonPath('user', null);

        $this
            ->withSession([
                'impersonating' => true,
                'impersonated_tenant_id' => 42,
                'impersonated_tenant_name' => (
                    'Session Laboratory'
                ),
                'impersonated_user_id' => 7,
                'impersonated_user_email' => (
                    'session-admin@example.test'
                ),
            ])
            ->withHeaders($headers)
            ->getJson('/api/landlord/impersonate/status')
            ->assertOk()
            ->assertJsonPath(
                'is_impersonating',
                true
            )
            ->assertJsonPath('tenant.id', 42)
            ->assertJsonPath(
                'tenant.name',
                'Session Laboratory'
            )
            ->assertJsonPath('user.id', 7)
            ->assertJsonPath(
                'user.email',
                'session-admin@example.test'
            );

        $this
            ->withHeaders($headers)
            ->postJson('/api/landlord/impersonate/stop')
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Stopped impersonating'
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

        $this
            ->withHeaders($headers)
            ->getJson('/api/landlord/impersonate/status')
            ->assertOk()
            ->assertJsonPath(
                'is_impersonating',
                false
            )
            ->assertJsonPath('tenant', null)
            ->assertJsonPath('user', null);

        $this->assertLandlordContext();
    }

    public function test_missing_and_suspended_tenant_contracts_are_preserved(): void
    {
        $headers = $this->superadminHeaders();

        $this
            ->withHeaders($headers)
            ->postJson(
                '/api/landlord/impersonate/999999999'
            )
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'Tenant not found'
            );

        $this->assertLandlordContext();

        $registration = $this->registerLandlordTestTenant();
        $tenant = $registration['tenant'];

        $tenant->update([
            'is_active' => false,
        ]);

        $this
            ->withHeaders($headers)
            ->postJson(
                '/api/landlord/impersonate/'
                .$tenant->getKey()
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Cannot impersonate a suspended tenant'
            );

        $this->assertLandlordContext();
    }

    private function assertLandlordContext(): void
    {
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
