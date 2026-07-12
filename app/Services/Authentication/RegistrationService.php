<?php

namespace App\Services\Authentication;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class RegistrationService
{
    public function __construct(
        protected TenantSwitcher $tenantSwitcher,
        protected SessionService $sessionService,
        protected AuthenticationService $authenticationService,
    ) {
    }

    /**
     * Provision a new tenant and create its first administrator.
     *
     * @throws Throwable
     */
    public function register(array $data): array
    {
        $tenant = null;
        $database = null;

        $domain = strtolower(trim($data['tenant_domain']));

        try {
            $database = $this->generateDatabaseName($domain);

            $this->createDatabase($database);

            $tenant = Tenant::query()->create([
                'name' => trim($data['tenant_name']),
                'domain' => $domain,
                'database' => $database,
                'settings' => [],
                'is_active' => true,
                'trial_ends_at' => now()->addDays(30),
            ]);

            $this->tenantSwitcher->switch($tenant);

            $this->runTenantMigrations();

            $administrator = TenantUser::query()->create([
                'name' => trim($data['name']),
                'email' => strtolower(trim($data['email'])),
                'password' => Hash::make($data['password']),
                'role' => 'admin',
                'is_active' => true,
            ]);

            $token = $this->authenticationService->createToken(
                $administrator,
                'auth_token'
            );

            $this->sessionService->startTenantSession(
                $tenant,
                $administrator
            );

            Log::info('Tenant registration completed successfully.', [
                'tenant_id' => $tenant->id,
                'tenant_domain' => $tenant->domain,
                'tenant_database' => $tenant->database,
                'administrator_id' => $administrator->id,
                'administrator_email' => $administrator->email,
            ]);

            return [
                'message' => 'Tenant and administrator created successfully.',

                'user' => [
                    'id' => $administrator->id,
                    'name' => $administrator->name,
                    'email' => $administrator->email,
                    'role' => $administrator->role,
                    'is_active' => (bool) $administrator->is_active,
                ],

                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                    'database' => $tenant->database,
                    'is_active' => (bool) $tenant->is_active,
                    'trial_ends_at' => $tenant->trial_ends_at,
                ],

                'token' => $token,
                'guard' => 'tenant',
                'is_superadmin' => false,
            ];
        } catch (Throwable $exception) {
            $this->cleanupFailedProvisioning(
                tenant: $tenant,
                database: $database,
                originalException: $exception,
            );

            throw $exception;
        }
    }

    /**
     * Validate tenant registration business rules.
     */
    public function validate(array $data): void
    {
        $domain = strtolower(trim($data['tenant_domain']));

        if (
            ! preg_match(
                '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $domain
            )
        ) {
            throw ValidationException::withMessages([
                'tenant_domain' => [
                    'The tenant domain may contain lowercase letters, numbers and single hyphens only.',
                ],
            ]);
        }

        if (strlen($domain) > 63) {
            throw ValidationException::withMessages([
                'tenant_domain' => [
                    'The tenant domain may not be greater than 63 characters.',
                ],
            ]);
        }

        if (
            Tenant::query()
                ->where('domain', $domain)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'tenant_domain' => [
                    'The selected tenant domain already exists.',
                ],
            ]);
        }
    }

    /**
     * Create the physical tenant database.
     */
    private function createDatabase(string $database): void
    {
        $this->assertSafeDatabaseName($database);

        DB::connection('landlord')->statement(
            sprintf(
                'CREATE DATABASE `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                $database
            )
        );
    }

    /**
     * Run every tenant migration against the new database.
     */
    private function runTenantMigrations(): void
    {
        $exitCode = Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'Tenant migration failed: '.trim(Artisan::output())
            );
        }
    }

    /**
     * Generate a unique and safe tenant database name.
     */
    private function generateDatabaseName(string $domain): string
    {
        $slug = preg_replace(
            '/[^a-z0-9]+/',
            '_',
            strtolower($domain)
        );

        $slug = trim((string) $slug, '_');
        $slug = substr($slug, 0, 40);

        return sprintf(
            'tenant_%s_%s',
            strtolower(Str::random(8)),
            $slug
        );
    }

    /**
     * Remove tenant resources after failed provisioning.
     */
    private function cleanupFailedProvisioning(
        ?Tenant $tenant,
        ?string $database,
        Throwable $originalException
    ): void {
        Log::error('Tenant registration failed.', [
            'tenant_id' => $tenant?->id,
            'tenant_domain' => $tenant?->domain,
            'tenant_database' => $database,
            'error' => $originalException->getMessage(),
        ]);

        try {
            $this->tenantSwitcher->restore();
        } catch (Throwable $exception) {
            Log::error('Failed to restore landlord context.', [
                'error' => $exception->getMessage(),
            ]);
        }

        if ($tenant !== null) {
            try {
                Tenant::query()
                    ->whereKey($tenant->getKey())
                    ->delete();
            } catch (Throwable $exception) {
                Log::error('Failed to remove tenant record.', [
                    'tenant_id' => $tenant->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        if ($database !== null) {
            try {
                $this->assertSafeDatabaseName($database);

                DB::connection('landlord')->statement(
                    sprintf(
                        'DROP DATABASE IF EXISTS `%s`',
                        $database
                    )
                );
            } catch (Throwable $exception) {
                Log::error('Failed to remove tenant database.', [
                    'database' => $database,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * Prevent unsafe database identifiers in raw SQL statements.
     */
    private function assertSafeDatabaseName(string $database): void
    {
        if (! preg_match('/^[a-zA-Z0-9_]+$/', $database)) {
            throw new RuntimeException(
                'Unsafe tenant database name detected.'
            );
        }
    }
}