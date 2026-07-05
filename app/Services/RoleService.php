<?php

namespace App\Services;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleService
{
    /**
     * Seed roles and permissions for a new tenant
     */
    public function seedRolesAndPermissions(): void
    {
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            // Define permissions
            $permissions = $this->getPermissions();
            
            foreach ($permissions as $permission) {
                Permission::firstOrCreate([
                    'name' => $permission,
                    'guard_name' => 'sanctum'
                ]);
            }

            // Define roles
            $roles = $this->getRoles();
            
            foreach ($roles as $roleName => $rolePermissions) {
                $role = Role::firstOrCreate([
                    'name' => $roleName,
                    'guard_name' => 'sanctum'
                ]);
                
                foreach ($rolePermissions as $permission) {
                    $role->givePermissionTo($permission);
                }
            }

            Log::info('Roles and permissions seeded for tenant');

        } catch (\Exception $e) {
            Log::error('Error seeding roles and permissions:', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Assign a role to a user
     */
    public function assignRoleToUser(User $user, string $roleName): void
    {
        try {
            // Try Spatie's assignRole method
            if (method_exists($user, 'assignRole')) {
                $role = Role::where('name', $roleName)->where('guard_name', 'sanctum')->first();
                if ($role) {
                    $user->assignRole($role);
                    Log::info("Role '{$roleName}' assigned to user: " . $user->email);
                    return;
                }
            }
            
            // Fallback: Directly update the role column
            $user->role = $roleName;
            $user->save();
            Log::info("Role '{$roleName}' set directly on user: " . $user->email);

        } catch (\Exception $e) {
            Log::error('Error assigning role:', ['error' => $e->getMessage()]);
            
            // Ultimate fallback: Directly update the role column
            try {
                $user->role = $roleName;
                $user->save();
                Log::info("Role '{$roleName}' set directly on user (fallback): " . $user->email);
            } catch (\Exception $e2) {
                Log::error('Fallback role assignment also failed:', ['error' => $e2->getMessage()]);
                throw $e;
            }
        }
    }

    /**
     * Get all permissions
     */
    private function getPermissions(): array
    {
        return [
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
            
            // Tenant Management
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
    }

    /**
     * Get all roles and their permissions
     */
    private function getRoles(): array
    {
        return [
            'superadmin' => $this->getPermissions(),
            
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
    }

    /**
     * Get all available roles
     */
    public function getAvailableRoles(): array
    {
        return [
            'superadmin' => 'Super Administrator - Full system access',
            'admin' => 'Administrator - Manage all lab operations',
            'lab_technician' => 'Lab Technician - Perform and enter test results',
            'receptionist' => 'Receptionist - Register patients and manage appointments',
            'doctor' => 'Doctor - View patient data and approve results',
        ];
    }
}