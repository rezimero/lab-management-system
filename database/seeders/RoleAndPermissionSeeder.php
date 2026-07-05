<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RoleAndPermissionSeeder extends Seeder
{
    protected $guard = 'sanctum';

    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define permissions grouped by module
        $permissions = [
            // Patient Management
            'view_patients',
            'create_patients',
            'edit_patients',
            'delete_patients',
            
            // Lab Test Management
            'view_lab_tests',
            'create_lab_tests',
            'edit_lab_tests',
            'delete_lab_tests',
            
            // Test Results Management
            'view_test_results',
            'create_test_results',
            'edit_test_results',
            'delete_test_results',
            'approve_test_results',
            
            // User Management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'assign_roles',
            
            // Tenant Management (Superadmin only)
            'view_tenants',
            'create_tenants',
            'edit_tenants',
            'delete_tenants',
            
            // Reports
            'view_reports',
            'generate_reports',
            
            // Settings
            'manage_settings',
        ];

        // Create permissions with sanctum guard
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $this->guard
            ]);
        }

        // Define roles and their permissions
        $roles = [
            'superadmin' => $permissions,
            
            'admin' => [
                'view_patients',
                'create_patients',
                'edit_patients',
                'delete_patients',
                'view_lab_tests',
                'create_lab_tests',
                'edit_lab_tests',
                'delete_lab_tests',
                'view_test_results',
                'create_test_results',
                'edit_test_results',
                'delete_test_results',
                'approve_test_results',
                'view_users',
                'create_users',
                'edit_users',
                'delete_users',
                'assign_roles',
                'view_reports',
                'generate_reports',
                'manage_settings',
            ],
            
            'lab_technician' => [
                'view_patients',
                'create_patients',
                'edit_patients',
                'view_lab_tests',
                'view_test_results',
                'create_test_results',
                'edit_test_results',
                'approve_test_results',
                'view_reports',
            ],
            
            'receptionist' => [
                'view_patients',
                'create_patients',
                'edit_patients',
                'view_lab_tests',
                'view_test_results',
                'view_reports',
            ],
            
            'doctor' => [
                'view_patients',
                'view_lab_tests',
                'view_test_results',
                'approve_test_results',
                'view_reports',
                'generate_reports',
            ],
        ];

        // Create roles and assign permissions
        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => $this->guard
            ]);
            
            // Sync permissions
            $permissionObjects = Permission::whereIn('name', $rolePermissions)
                ->where('guard_name', $this->guard)
                ->get();
            
            $role->syncPermissions($permissionObjects);
        }
    }
}