<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class TenantService
{
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Create a new tenant with its own database
     */
    public function createTenant(array $data): Tenant
    {
        // Generate unique database name
        $databaseName = 'tenant_' . Str::random(8) . '_' . strtolower(Str::slug($data['name']));

        Log::info('Creating tenant: ' . $data['name'] . ' with database: ' . $databaseName);

        // Create the tenant record
        $tenant = Tenant::create([
            'name' => $data['name'],
            'domain' => $data['domain'],
            'database' => $databaseName,
            'settings' => $data['settings'] ?? [],
            'trial_ends_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        Log::info('Tenant record created: ' . $tenant->id);

        try {
            // Create the physical database
            $this->createDatabase($databaseName);
            Log::info('Database created: ' . $databaseName);

            // Run migrations on the tenant database
            $this->runMigrations($tenant);
            Log::info('Migrations completed for tenant: ' . $tenant->id);

            // Seed roles and permissions
            $this->seedRolesAndPermissions($tenant);
            Log::info('Roles and permissions seeded for tenant: ' . $tenant->id);

            // Create admin user for the tenant
            $this->createAdminUser($tenant, $data);
            Log::info('Admin user created for tenant: ' . $tenant->id);

        } catch (\Exception $e) {
            Log::error('Tenant creation failed: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Clean up if something failed
            try {
                DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");
                Log::info('Cleaned up database: ' . $databaseName);
            } catch (\Exception $cleanupError) {
                Log::error('Cleanup failed: ' . $cleanupError->getMessage());
            }
            
            $tenant->delete();
            Log::info('Deleted tenant record: ' . $tenant->id);
            throw $e;
        }

        return $tenant;
    }

    /**
     * Create the physical database for tenant
     */
    private function createDatabase(string $databaseName): void
    {
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}` 
                       CHARACTER SET utf8mb4 
                       COLLATE utf8mb4_unicode_ci");
    }

    /**
     * Run migrations on tenant database
     */
    private function runMigrations(Tenant $tenant): void
    {
        // Explicitly set the tenant database connection
        config(['database.connections.tenant.database' => $tenant->database]);
        DB::purge('tenant');
        DB::connection('tenant')->reconnect();
        
        // Switch to tenant context
        $tenant->makeCurrent();
        
        Log::info('Running migrations on database: ' . DB::connection('tenant')->getDatabaseName());

        // Run migrations
        $exitCode = Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
        
        Log::info('Migration exit code: ' . $exitCode);
        Log::info('Migration output: ' . Artisan::output());
    }

    /**
     * Seed roles and permissions for the tenant
     */
    private function seedRolesAndPermissions(Tenant $tenant): void
    {
        // Explicitly set the tenant database connection
        config(['database.connections.tenant.database' => $tenant->database]);
        DB::purge('tenant');
        DB::connection('tenant')->reconnect();
        
        $tenant->makeCurrent();
        
        Log::info('Seeding roles for tenant: ' . $tenant->database);
        
        // Run the role seeder
        $exitCode = Artisan::call('db:seed', [
            '--class' => 'RoleAndPermissionSeeder',
            '--force' => true,
        ]);
        
        Log::info('Seeder exit code: ' . $exitCode);
        Log::info('Seeder output: ' . Artisan::output());
    }

    /**
     * Create admin user for the tenant
     * IMPORTANT: New registrations always get 'admin' role, never 'superadmin'
     */
    private function createAdminUser(Tenant $tenant, array $data): void
    {
        // Explicitly set the tenant database connection
        config(['database.connections.tenant.database' => $tenant->database]);
        DB::purge('tenant');
        DB::connection('tenant')->reconnect();
        
        $tenant->makeCurrent();

        Log::info('Creating admin user in database: ' . DB::connection('tenant')->getDatabaseName());

        $user = User::create([
            'name' => $data['admin_name'] ?? 'Admin',
            'email' => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
            'role' => 'admin', // ALWAYS 'admin' for new registrations
            'is_active' => true,
        ]);

        Log::info('User created: ' . $user->id);

        // Assign admin role using Spatie
        $this->roleService->assignRoleToUser($user, 'admin');
        
        Log::info('Admin role assigned to user: ' . $user->id);
    }

    /**
     * Delete a tenant and its database
     */
    public function deleteTenant(Tenant $tenant): void
    {
        // Drop the tenant database
        DB::statement("DROP DATABASE IF EXISTS `{$tenant->database}`");
        
        // Delete the tenant record
        $tenant->delete();
    }

    /**
     * Get all tenants
     */
    public function getAllTenants()
    {
        return Tenant::all();
    }

    /**
     * Get tenant by domain
     */
    public function getTenantByDomain(string $domain): ?Tenant
    {
        return Tenant::where('domain', $domain)->first();
    }

    /**
     * Activate a tenant
     */
    public function activateTenant(Tenant $tenant): void
    {
        $tenant->update(['is_active' => true]);
    }

    /**
     * Deactivate a tenant
     */
    public function deactivateTenant(Tenant $tenant): void
    {
        $tenant->update(['is_active' => false]);
    }
}