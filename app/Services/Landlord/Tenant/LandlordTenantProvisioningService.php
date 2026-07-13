<?php

namespace App\Services\Landlord\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Landlord\TenantContextRunner;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class LandlordTenantProvisioningService
{
    public function __construct(
        protected TenantContextRunner $contextRunner,
    ) {
    }

    /**
     * Provision a tenant database and its initial administrator.
     *
     * @param array{
     *     name: string,
     *     domain: string,
     *     admin_name: string,
     *     admin_email: string,
     *     admin_password: string
     * } $data
     */
    public function provision(array $data): Tenant
    {
        $tenant = null;
        $database = $this->generateDatabaseName(
            $data['domain']
        );

        try {
            $this->createDatabase($database);

            $tenant = Tenant::query()->create([
                'name' => $data['name'],
                'domain' => $data['domain'],
                'database' => $database,
                'is_active' => true,
                'trial_ends_at' => now()->addDays(30),
            ]);

            $this->contextRunner->run(
                $tenant,
                function () use ($data): void {
                    $this->runMigrations();
                    $this->createAdministrator($data);
                }
            );

            return $tenant->refresh();
        } catch (Throwable $exception) {
            $this->cleanupFailure(
                tenant: $tenant,
                database: $database,
                originalException: $exception,
            );

            throw $exception;
        }
    }

    private function generateDatabaseName(
        string $domain
    ): string {
        $domainPart = strtolower(
            str_replace(' ', '-', $domain)
        );

        $database = sprintf(
            'tenant_%s_%s',
            uniqid(),
            $domainPart
        );

        $database = preg_replace(
            '/[^a-zA-Z0-9_-]/',
            '',
            $database
        );

        if (
            ! is_string($database)
            || $database === ''
        ) {
            throw new RuntimeException(
                'Failed to generate a tenant database name.'
            );
        }

        $this->assertSafeDatabaseName($database);

        return $database;
    }

    private function createDatabase(
        string $database
    ): void {
        $this->assertSafeDatabaseName($database);

        DB::connection('landlord')->statement(
            sprintf(
                'CREATE DATABASE IF NOT EXISTS `%s` '
                .'CHARACTER SET utf8mb4 '
                .'COLLATE utf8mb4_unicode_ci',
                $database
            )
        );
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
     * @param array{
     *     admin_name: string,
     *     admin_email: string,
     *     admin_password: string
     * } $data
     */
    private function createAdministrator(
        array $data
    ): void {
        TenantUser::query()->create([
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
        string $database,
        Throwable $originalException
    ): void {
        Log::error('Landlord tenant provisioning failed.', [
            'tenant_id' => $tenant?->id,
            'tenant_domain' => $tenant?->domain,
            'tenant_database' => $database,
            'error' => $originalException->getMessage(),
        ]);

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

        try {
            $this->assertSafeDatabaseName($database);

            DB::connection('landlord')->statement(
                sprintf(
                    'DROP DATABASE IF EXISTS `%s`',
                    $database
                )
            );
        } catch (Throwable $cleanupException) {
            Log::error(
                'Failed to remove incomplete tenant database.',
                [
                    'tenant_database' => $database,
                    'error' => $cleanupException->getMessage(),
                ]
            );
        }
    }

    private function assertSafeDatabaseName(
        string $database
    ): void {
        if (
            ! preg_match(
                '/^[a-zA-Z0-9_-]+$/',
                $database
            )
        ) {
            throw new RuntimeException(
                'Unsafe tenant database name detected.'
            );
        }
    }
}