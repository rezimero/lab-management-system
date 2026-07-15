<?php

namespace App\Services\Tenant\Provisioning;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Authentication\TenantSwitcher;
use App\Services\Landlord\TenantContextRunner;
use Closure;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class TenantProvisioner
{
    public function __construct(
        protected TenantDatabaseManager $databaseManager,
        protected TenantContextRunner $contextRunner,
        protected TenantSwitcher $tenantSwitcher,
    ) {
    }

    /**
     * Provision a tenant database and its first administrator.
     *
     * The within-tenant callback runs before tenant context is restored.
     * The after-restore callback runs in landlord context. A failure in
     * either callback removes the tenant record and physical database.
     *
     * @param array{
     *     tenant_name: string,
     *     tenant_domain: string,
     *     admin_name: string,
     *     admin_email: string,
     *     admin_password: string,
     *     settings?: array<string, mixed>
     * } $data
     */
    public function provision(
        array $data,
        ?Closure $withinTenant = null,
        ?Closure $afterRestore = null
    ): TenantProvisioningResult {
        $tenant = null;
        $database = null;

        try {
            $database = $this->databaseManager
                ->generateName($data['tenant_domain']);

            $this->databaseManager->create($database);

            $tenant = $this->createTenantRecord(
                $data,
                $database
            );

            $result = $this->contextRunner->run(
                $tenant,
                function () use (
                    $tenant,
                    $data,
                    $withinTenant
                ): TenantProvisioningResult {
                    $this->runMigrations();

                    $administrator = $this
                        ->createAdministrator($data);

                    $contextResult = $withinTenant !== null
                        ? $withinTenant(
                            $tenant,
                            $administrator
                        )
                        : null;

                    return new TenantProvisioningResult(
                        tenant: $tenant,
                        administrator: $administrator,
                        contextResult: $contextResult,
                    );
                }
            );

            if ($afterRestore !== null) {
                $afterRestore(
                    $result->tenant,
                    $result->administrator
                );
            }

            Log::info('Tenant provisioning completed.', [
                'tenant_id' => $tenant->id,
                'tenant_domain' => $tenant->domain,
                'tenant_database' => $tenant->database,
                'administrator_id' => (
                    $result->administrator->id
                ),
            ]);

            return $result;
        } catch (Throwable $exception) {
            $this->cleanupFailure(
                tenant: $tenant,
                database: $database,
                originalException: $exception,
            );

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createTenantRecord(
        array $data,
        string $database
    ): Tenant {
        $attributes = [
            'name' => $data['tenant_name'],
            'domain' => $data['tenant_domain'],
            'database' => $database,
            'is_active' => true,
            'trial_ends_at' => now()->addDays(30),
        ];

        if (array_key_exists('settings', $data)) {
            $attributes['settings'] = $data['settings'];
        }

        return Tenant::query()->create($attributes);
    }

    private function runMigrations(): void
    {
        $exitCode = Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'Tenant migration failed: '
                .trim(Artisan::output())
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function createAdministrator(
        array $data
    ): TenantUser {
        return TenantUser::query()->create([
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => Hash::make(
                $data['admin_password']
            ),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function cleanupFailure(
        ?Tenant $tenant,
        ?string $database,
        Throwable $originalException
    ): void {
        Log::error('Tenant provisioning failed.', [
            'tenant_id' => $tenant?->id,
            'tenant_domain' => $tenant?->domain,
            'tenant_database' => $database,
            'error' => $originalException->getMessage(),
        ]);

        try {
            $this->tenantSwitcher->restore();
        } catch (Throwable $cleanupException) {
            Log::error(
                'Failed to restore landlord context.',
                [
                    'error' => $cleanupException->getMessage(),
                ]
            );
        }

        if ($tenant !== null) {
            try {
                Tenant::query()
                    ->whereKey($tenant->getKey())
                    ->delete();
            } catch (Throwable $cleanupException) {
                Log::error(
                    'Failed to remove incomplete tenant record.',
                    [
                        'tenant_id' => $tenant->id,
                        'error' => (
                            $cleanupException->getMessage()
                        ),
                    ]
                );
            }
        }

        if ($database !== null) {
            try {
                $this->databaseManager->drop($database);
            } catch (Throwable $cleanupException) {
                Log::error(
                    'Failed to remove incomplete tenant database.',
                    [
                        'tenant_database' => $database,
                        'error' => (
                            $cleanupException->getMessage()
                        ),
                    ]
                );
            }
        }
    }
}