<?php

namespace Tests\Feature\Authentication;

use App\Models\Tenant\User as TenantUser;
use App\Services\Authentication\TenantSwitcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Authentication\TenantAuthenticationTestCase;

class TenantRegistrationTest extends TenantAuthenticationTestCase
{
    public function test_registration_provisions_a_complete_tenant_database(): void
    {
        $registration = $this->registerTestTenant();
        $tenant = $registration['tenant'];

        $this->assertTrue(
            $this->tenantDatabaseExists($tenant->database),
            'The tenant database was not created.'
        );

        app(TenantSwitcher::class)->switch($tenant);

        $schema = Schema::connection('tenant');

        $this->assertTrue($schema->hasTable('users'));
        $this->assertTrue($schema->hasTable('departments'));
        $this->assertTrue($schema->hasTable('patients'));
        $this->assertTrue($schema->hasTable('lab_tests'));
        $this->assertTrue($schema->hasTable('test_results'));

        $this->assertTrue(
            $schema->hasColumn('users', 'role')
        );

        $this->assertTrue(
            $schema->hasColumn('users', 'is_active')
        );

        $this->assertDatabaseHas(
            'users',
            [
                'email' => $registration['email'],
                'role' => 'admin',
                'is_active' => true,
            ],
            'tenant'
        );

        $this->assertDatabaseHas(
            'personal_access_tokens',
            [
                'tenant_id' => $tenant->id,
                'tokenable_type' => (
                    new TenantUser()
                )->getMorphClass(),
                'tokenable_id' => 1,
                'name' => 'auth_token',
            ],
            'landlord'
        );

        if ($schema->hasTable('personal_access_tokens')) {
            $this->assertDatabaseCount(
                'personal_access_tokens',
                0,
                'tenant'
            );
        }

        $migrations = DB::connection('tenant')
            ->table('migrations')
            ->pluck('migration')
            ->all();

        $this->assertContains(
            '2026_07_07_172216_create_departments_table',
            $migrations
        );

        $this->assertContains(
            '2026_07_11_174653_add_role_and_is_active_to_users_table',
            $migrations
        );
    }
}