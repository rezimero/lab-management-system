<?php

namespace Tests\Support\Authentication;

use App\Services\Authentication\SessionService;
use App\Services\Authentication\TenantSwitcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;
use Throwable;

abstract class AuthenticationTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertAuthenticationTestEnvironment();
        $this->restoreLandlordContext();
        $this->clearAuthenticationState();
    }

    protected function tearDown(): void
    {
        try {
            $this->clearAuthenticationState();
            $this->restoreLandlordContext();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * Build tenant bearer-token headers.
     *
     * @return array<string, string>
     */
    protected function tenantHeaders(
        string $token,
        string $domain
    ): array {
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token,
            'X-Tenant-Domain' => $domain,
        ];
    }

    /**
     * Build ordinary bearer-token headers.
     *
     * @return array<string, string>
     */
    protected function bearerHeaders(string $token): array
    {
        return [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$token,
        ];
    }

    /**
     * Restore the landlord database as the active context.
     */
    protected function restoreLandlordContext(): void
    {
        try {
            app(TenantSwitcher::class)->restore();
        } catch (Throwable) {
            config([
                'database.default' => 'landlord',
            ]);

            DB::disconnect('tenant');
            DB::purge('tenant');
            DB::purge('landlord');
            DB::reconnect('landlord');
        }
    }

    /**
     * Clear cached authentication and authorization state.
     */
    protected function clearAuthenticationState(): void
    {
        app(SessionService::class)->clear();

        Auth::forgetGuards();

        if (app()->bound('superadmin.override')) {
            app()->forgetInstance('superadmin.override');
        }
    }

    /**
     * Enforce the isolated MySQL test environment.
     */
    private function assertAuthenticationTestEnvironment(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException(
                'Authentication tests may run only in the testing environment.'
            );
        }

        $database = DB::connection('landlord')
            ->getDatabaseName();

        if ($database !== 'lab_saas_landlord_test') {
            throw new RuntimeException(
                sprintf(
                    'Unsafe landlord database [%s]. '
                    .'Expected [lab_saas_landlord_test].',
                    $database
                )
            );
        }

        if (
            DB::connection('landlord')->getDriverName()
            !== 'mysql'
        ) {
            throw new RuntimeException(
                'Authentication tests require MySQL.'
            );
        }

        if (
            ! Schema::connection('landlord')
                ->hasTable('tenants')
        ) {
            throw new RuntimeException(
                'The landlord tenants table does not exist.'
            );
        }

        if (
            ! Schema::connection('landlord')
                ->hasTable('users')
        ) {
            throw new RuntimeException(
                'The landlord users table does not exist.'
            );
        }

        if (
            ! Schema::connection('landlord')
                ->hasColumn(
                    'personal_access_tokens',
                    'tenant_id'
                )
        ) {
            throw new RuntimeException(
                'The landlord token table is missing tenant isolation.'
            );
        }
    }
}