<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TenantService
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Create a new tenant with its own database.
     */
    public function createTenant(array $data): Tenant
    {
        // Generate unique database name
        $databaseName = 'tenant_' . Str::random(8) . '_' . strtolower(Str::slug($data['name']));

        Log::info('Creating tenant: ' . $data['name'] . ' with database: ' . $databaseName);

        // Create landlord tenant record
        $tenant = Tenant::create([
            'name' => $data['name'],
            'domain' => $data['domain'],
            'database' => $databaseName,
            'settings' => $data['settings'] ?? [],
            'trial_ends_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        Log::info('Tenant record created: ' . $tenant->id);

        // Create default profile in landlord database
        $this->createTenantProfile($tenant, $data);

        try {

            // Create physical tenant database
            $this->createDatabase($databaseName);
            Log::info('Database created: ' . $databaseName);

            // Run tenant migrations
            $this->runMigrations($tenant);
            Log::info('Migrations completed for tenant: ' . $tenant->id);

            // Seed roles & permissions
            $this->seedRolesAndPermissions($tenant);
            Log::info('Roles and permissions seeded for tenant: ' . $tenant->id);

            // Create tenant administrator
            $this->createAdminUser($tenant, $data);
            Log::info('Admin user created for tenant: ' . $tenant->id);

        } catch (\Exception $e) {

            Log::error('Tenant creation failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());

            try {

                DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");

                Log::info('Cleaned up database: ' . $databaseName);

            } catch (\Exception $cleanupError) {

                Log::error('Cleanup failed: ' . $cleanupError->getMessage());

            }

            // Profile will be deleted automatically because of cascadeOnDelete()
            $tenant->delete();

            Log::info('Deleted tenant record: ' . $tenant->id);

            throw $e;
        }

        return $tenant;
    }

    /**
     * Create default laboratory profile.
     */
    private function createTenantProfile(Tenant $tenant, array $data): void
    {
        $tenant->profile()->create([

            'lab_name' => $data['name'],

            'primary_color' => '#1976d2',
            'secondary_color' => '#424242',
            'accent_color' => '#2e7d32',

            'country' => 'Nigeria',

            'email' => $data['admin_email'] ?? null,

            'phone' => null,
            'website' => null,
            'tagline' => null,
            'address' => null,
            'city' => null,
            'state' => null,

            'license_number' => null,
            'registration_number' => null,
            'tin' => null,

            'report_footer' => null,
            'director_name' => null,
            'director_signature' => null,

            'invoice_footer' => null,
            'bank_name' => null,
            'account_name' => null,
            'account_number' => null,
        ]);

        Log::info('Tenant profile created for tenant: ' . $tenant->id);
    }

    /**
     * Create physical tenant database.
     */
    private function createDatabase(string $databaseName): void
    {
        DB::statement("
            CREATE DATABASE IF NOT EXISTS `{$databaseName}`
            CHARACTER SET utf8mb4
            COLLATE utf8mb4_unicode_ci
        ");
    }

    /**
     * Run tenant migrations.
     */
    private function runMigrations(Tenant $tenant): void
    {
        config([
            'database.connections.tenant.database' => $tenant->database,
        ]);

        DB::purge('tenant');
        DB::connection('tenant')->reconnect();

        $tenant->makeCurrent();

        Log::info('Running migrations on database: ' . DB::connection('tenant')->getDatabaseName());

        $exitCode = Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        Log::info('Migration exit code: ' . $exitCode);
        Log::info('Migration output: ' . Artisan::output());
    }

    /**
     * Seed tenant roles and permissions.
     */
    private function seedRolesAndPermissions(Tenant $tenant): void
    {
        config([
            'database.connections.tenant.database' => $tenant->database,
        ]);

        DB::purge('tenant');
        DB::connection('tenant')->reconnect();

        $tenant->makeCurrent();

        Log::info('Seeding roles for tenant: ' . $tenant->database);

        $exitCode = Artisan::call('db:seed', [
            '--class' => 'RoleAndPermissionSeeder',
            '--force' => true,
        ]);

        Log::info('Seeder exit code: ' . $exitCode);
        Log::info('Seeder output: ' . Artisan::output());
    }

    /**
     * Create tenant administrator.
     */
    private function createAdminUser(Tenant $tenant, array $data): void
    {
        config([
            'database.connections.tenant.database' => $tenant->database,
        ]);

        DB::purge('tenant');
        DB::connection('tenant')->reconnect();

        $tenant->makeCurrent();

        Log::info('Creating admin user in database: ' . DB::connection('tenant')->getDatabaseName());

        $user = User::create([
            'name' => $data['admin_name'] ?? 'Admin',
            'email' => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
            'role' => 'admin',
            'is_active' => true,
        ]);

        Log::info('User created: ' . $user->id);

        $this->roleService->assignRoleToUser($user, 'admin');

        Log::info('Admin role assigned to user: ' . $user->id);
    }

    /**
     * Delete tenant and database.
     */
    public function deleteTenant(Tenant $tenant): void
    {
        DB::statement("DROP DATABASE IF EXISTS `{$tenant->database}`");

        $tenant->delete();
    }

    /**
     * Get all tenants.
     */
    public function getAllTenants()
    {
        return Tenant::all();
    }

    /**
     * Get tenant by domain.
     */
    public function getTenantByDomain(string $domain): ?Tenant
    {
        return Tenant::where('domain', $domain)->first();
    }

    /**
     * Activate tenant.
     */
    public function activateTenant(Tenant $tenant): void
    {
        $tenant->update([
            'is_active' => true,
        ]);
    }

    /**
     * Deactivate tenant.
     */
    public function deactivateTenant(Tenant $tenant): void
    {
        $tenant->update([
            'is_active' => false,
        ]);
    }
}