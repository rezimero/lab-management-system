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

        // Create the tenant record
        $tenant = Tenant::create([
            'name' => $data['name'],
            'domain' => $data['domain'],
            'database' => $databaseName,
            'settings' => $data['settings'] ?? [],
            'trial_ends_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        // Create the physical database
        $this->createDatabase($databaseName);

        // Run migrations on the tenant database
        $this->runMigrations($tenant);

        // Seed roles and permissions
        $this->seedRolesAndPermissions($tenant);

        // Create admin user for the tenant
        $this->createAdminUser($tenant, $data);

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
        
        // Switch to tenant context
        $tenant->makeCurrent();
        
        Log::info('Running migrations on database: ' . DB::connection('tenant')->getDatabaseName());

        // Run migrations
        Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);
        
        Log::info('Migration output: ' . Artisan::output());
    }

    /**
     * Seed roles and permissions for the tenant
     */
    private function seedRolesAndPermissions(Tenant $tenant): void
    {
        $tenant->makeCurrent();
        
        // Run the role seeder
        Artisan::call('db:seed', [
            '--class' => 'RoleAndPermissionSeeder',
            '--force' => true,
        ]);
        
        Log::info('Roles and permissions seeded for tenant: ' . $tenant->database);
    }

    /**
     * Create admin user for the tenant
     */
    private function createAdminUser(Tenant $tenant, array $data): void
    {
        $tenant->makeCurrent();

        $user = User::create([
            'name' => $data['admin_name'] ?? 'Admin',
            'email' => $data['admin_email'],
            'password' => Hash::make($data['admin_password']),
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Assign admin role
        $this->roleService->assignRoleToUser($user, 'admin');
        
        Log::info('Admin user created with role: admin');
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