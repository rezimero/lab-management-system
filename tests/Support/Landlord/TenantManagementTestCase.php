<?php

namespace Tests\Support\Landlord;

use App\Models\Tenant;
use App\Services\Landlord\TenantContextRunner;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\Support\Authentication\LandlordAuthenticationTestCase;

abstract class TenantManagementTestCase extends LandlordAuthenticationTestCase
{
    /**
     * Domains provisioned directly through the landlord tenant API.
     *
     * @var array<int, string>
     */
    private array $managedTenantDomains = [];

    /**
     * Databases provisioned directly through the landlord tenant API.
     *
     * @var array<int, string>
     */
    private array $managedTenantDatabases = [];

    protected function tearDown(): void
    {
        try {
            $this->cleanupManagedTenantResources();
        } finally {
            parent::tearDown();
        }
    }

    /**
     * Create and authenticate an active platform superadmin.
     *
     * @return array<string, string>
     */
    protected function superadminHeaders(): array
    {
        $superadmin = $this->createTestLandlordUser(
            role: 'superadmin',
            isActive: true
        );

        $response = $this->loginLandlord($superadmin);

        $response
            ->assertOk()
            ->assertJsonPath('guard', 'landlord')
            ->assertJsonPath('is_superadmin', true);

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        return $this->bearerHeaders($token);
    }

    /**
     * Generate a safe domain whose resulting database can be deleted
     * only by automated tests.
     */
    protected function uniqueManagedDomain(
        string $purpose
    ): string {
        return sprintf(
            'test_landlord_%s_%s',
            $purpose,
            strtolower(Str::random(10))
        );
    }

    protected function trackManagedTenantDomain(
        string $domain
    ): void {
        if (! in_array(
            $domain,
            $this->managedTenantDomains,
            true
        )) {
            $this->managedTenantDomains[] = $domain;
        }
    }

    protected function trackManagedTenantDatabase(
        string $database
    ): void {
        if (! in_array(
            $database,
            $this->managedTenantDatabases,
            true
        )) {
            $this->managedTenantDatabases[] = $database;
        }
    }

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
     * Execute a callback inside a tenant and always restore landlord.
     */
    protected function runInTenant(
        Tenant $tenant,
        Closure $operation
    ): mixed {
        return app(TenantContextRunner::class)->run(
            $tenant,
            $operation
        );
    }

    private function cleanupManagedTenantResources(): void
    {
        $this->restoreLandlordContext();

        $databases = $this->managedTenantDatabases;

        foreach ($this->managedTenantDomains as $domain) {
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

        foreach (array_unique($databases) as $database) {
            $this->dropManagedTenantDatabase($database);
        }

        $this->managedTenantDomains = [];
        $this->managedTenantDatabases = [];
    }

    /**
     * Find orphaned databases created before tenant-record creation
     * completed or before an exception was thrown.
     *
     * @return array<int, string>
     */
    private function tenantDatabasesForDomain(
        string $domain
    ): array {
        $rows = DB::connection('landlord')->select(
            'SELECT SCHEMA_NAME
             FROM INFORMATION_SCHEMA.SCHEMATA
             WHERE SCHEMA_NAME LIKE ?',
            ['tenant\_%']
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
                        '_'.$domain
                    )
            )
            ->values()
            ->all();
    }

    private function dropManagedTenantDatabase(
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