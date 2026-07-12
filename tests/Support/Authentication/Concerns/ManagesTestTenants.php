<?php

namespace Tests\Support\Authentication\Concerns;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

trait ManagesTestTenants
{
    /**
     * Tenant domains created by the current test.
     *
     * @var array<int, string>
     */
    private array $trackedTenantDomains = [];

    /**
     * Tenant databases created by the current test.
     *
     * @var array<int, string>
     */
    private array $trackedTenantDatabases = [];

    /**
     * Register a temporary tenant through the real API.
     *
     * @return array{
     *     tenant: Tenant,
     *     domain: string,
     *     email: string,
     *     password: string,
     *     token: string
     * }
     */
    protected function registerTestTenant(
        string $prefix = 'auth'
    ): array {
        $domain = $this->uniqueTenantDomain($prefix);
        $email = $domain.'@example.test';
        $password = 'password';

        $this->trackTenantDomain($domain);

        $response = $this->postJson('/api/register', [
            'name' => 'Tenant Administrator',
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $password,
            'tenant_name' => 'Authentication Test Laboratory',
            'tenant_domain' => $domain,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.email', $email)
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonPath('user.is_active', true)
            ->assertJsonPath('tenant.domain', $domain)
            ->assertJsonPath('guard', 'tenant')
            ->assertJsonPath('is_superadmin', false);

        $tenant = Tenant::query()
            ->where('domain', $domain)
            ->firstOrFail();

        $this->trackTenantDatabase($tenant->database);

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $this->restoreLandlordContext();

        return [
            'tenant' => $tenant,
            'domain' => $domain,
            'email' => $email,
            'password' => $password,
            'token' => $token,
        ];
    }

    /**
     * Track a tenant domain for cleanup.
     */
    protected function trackTenantDomain(
        string $domain
    ): void {
        if (! in_array(
            $domain,
            $this->trackedTenantDomains,
            true
        )) {
            $this->trackedTenantDomains[] = $domain;
        }
    }

    /**
     * Generate a unique test domain.
     */
    protected function uniqueTenantDomain(
        string $prefix
    ): string {
        return sprintf(
            'test-%s-%s',
            $prefix,
            strtolower(Str::random(10))
        );
    }

    /**
     * Determine whether a physical database exists.
     */
    protected function tenantDatabaseExists(
        string $database
    ): bool {
        return DB::connection('landlord')
            ->selectOne(
                'SELECT SCHEMA_NAME
                 FROM INFORMATION_SCHEMA.SCHEMATA
                 WHERE SCHEMA_NAME = ?',
                [$database]
            ) !== null;
    }

    /**
     * Find generated tenant databases for a domain.
     *
     * @return array<int, string>
     */
    protected function tenantDatabasesForDomain(
        string $domain
    ): array {
        $slug = str_replace(
            '-',
            '_',
            strtolower($domain)
        );

        $rows = DB::connection('landlord')->select(
            'SELECT SCHEMA_NAME
             FROM INFORMATION_SCHEMA.SCHEMATA
             WHERE SCHEMA_NAME LIKE ?',
            ['tenant\_%\_'.$slug]
        );

        return collect($rows)
            ->map(
                static fn (object $row): string =>
                    (string) $row->SCHEMA_NAME
            )
            ->filter(
                static fn (string $database): bool =>
                    str_ends_with(
                        $database,
                        '_'.$slug
                    )
            )
            ->values()
            ->all();
    }

    /**
     * Remove tenants and databases created by the test.
     */
    protected function cleanupTestTenants(): void
    {
        $this->restoreLandlordContext();

        $databases = $this->trackedTenantDatabases;

        foreach (
            $this->trackedTenantDomains as $domain
        ) {
            $tenant = Tenant::query()
                ->where('domain', $domain)
                ->first();

            if ($tenant !== null) {
                $databases[] = $tenant->database;
                $tenant->delete();
            }

            $databases = array_merge(
                $databases,
                $this->tenantDatabasesForDomain($domain)
            );
        }

        foreach (
            array_unique($databases) as $database
        ) {
            $this->dropTestTenantDatabase($database);
        }

        $this->trackedTenantDomains = [];
        $this->trackedTenantDatabases = [];
    }

    /**
     * Track a physical tenant database for cleanup.
     */
    private function trackTenantDatabase(
        string $database
    ): void {
        if (! in_array(
            $database,
            $this->trackedTenantDatabases,
            true
        )) {
            $this->trackedTenantDatabases[] = $database;
        }
    }

    /**
     * Drop only databases created by automated tests.
     */
    private function dropTestTenantDatabase(
        string $database
    ): void {
        if (
            ! preg_match(
                '/^tenant_[a-z0-9_]+$/',
                $database
            )
            || ! str_contains($database, '_test_')
        ) {
            throw new RuntimeException(
                sprintf(
                    'Refusing to drop unsafe database [%s].',
                    $database
                )
            );
        }

        DB::connection('landlord')->statement(
            sprintf(
                'DROP DATABASE IF EXISTS `%s`',
                $database
            )
        );
    }
}