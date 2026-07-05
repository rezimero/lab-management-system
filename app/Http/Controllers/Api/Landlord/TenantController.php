<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Models\Patient;
use App\Models\LabTest;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

class TenantController extends Controller
{
    /**
     * List all tenants
     */
    public function index()
    {
        try {
            $tenants = Tenant::all();
            
            $tenantData = [];
            foreach ($tenants as $tenant) {
                $tenantData[] = $this->getTenantWithStats($tenant);
            }
            
            return response()->json([
                'tenants' => $tenantData,
                'total' => $tenants->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching tenants: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch tenants'], 500);
        }
    }

    /**
     * Get a single tenant with stats
     */
    public function show($id)
    {
        try {
            $tenant = Tenant::find($id);
            if (!$tenant) {
                return response()->json(['message' => 'Tenant not found'], 404);
            }
            
            return response()->json($this->getTenantWithStats($tenant));
        } catch (\Exception $e) {
            Log::error('Error fetching tenant: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch tenant'], 500);
        }
    }

    /**
     * Create a new tenant
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'domain' => 'required|string|max:255|unique:tenants,domain',
                'admin_name' => 'required|string|max:255',
                'admin_email' => 'required|string|email|max:255',
                'admin_password' => 'required|string|min:8',
            ]);

            $databaseName = 'tenant_' . uniqid() . '_' . strtolower(str_replace(' ', '-', $validated['domain']));
            $databaseName = preg_replace('/[^a-zA-Z0-9_-]/', '', $databaseName);

            DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}`");

            $tenant = Tenant::create([
                'name' => $validated['name'],
                'domain' => $validated['domain'],
                'database' => $databaseName,
                'is_active' => true,
                'trial_ends_at' => now()->addDays(30),
            ]);

            $this->setupTenant($tenant, $validated);

            return response()->json([
                'message' => 'Tenant created successfully',
                'tenant' => $this->getTenantWithStats($tenant),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating tenant: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create tenant: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update a tenant
     */
    public function update(Request $request, $id)
    {
        try {
            $tenant = Tenant::find($id);
            if (!$tenant) {
                return response()->json(['message' => 'Tenant not found'], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'domain' => 'sometimes|string|max:255|unique:tenants,domain,' . $id,
                'is_active' => 'sometimes|boolean',
            ]);

            $tenant->update($validated);

            return response()->json([
                'message' => 'Tenant updated successfully',
                'tenant' => $this->getTenantWithStats($tenant),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating tenant: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update tenant'], 500);
        }
    }

    /**
     * Delete a tenant
     */
    public function destroy($id)
    {
        try {
            $tenant = Tenant::find($id);
            if (!$tenant) {
                return response()->json(['message' => 'Tenant not found'], 404);
            }

            DB::statement("DROP DATABASE IF EXISTS `{$tenant->database}`");
            $tenant->delete();

            return response()->json(['message' => 'Tenant deleted successfully']);

        } catch (\Exception $e) {
            Log::error('Error deleting tenant: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete tenant'], 500);
        }
    }

    /**
     * Suspend a tenant
     */
    public function suspend($id)
    {
        try {
            $tenant = Tenant::find($id);
            if (!$tenant) {
                return response()->json(['message' => 'Tenant not found'], 404);
            }

            $tenant->update(['is_active' => false]);

            return response()->json([
                'message' => 'Tenant suspended successfully',
                'tenant' => $this->getTenantWithStats($tenant),
            ]);

        } catch (\Exception $e) {
            Log::error('Error suspending tenant: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to suspend tenant'], 500);
        }
    }

    /**
     * Activate a tenant
     */
    public function activate($id)
    {
        try {
            $tenant = Tenant::find($id);
            if (!$tenant) {
                return response()->json(['message' => 'Tenant not found'], 404);
            }

            $tenant->update(['is_active' => true]);

            return response()->json([
                'message' => 'Tenant activated successfully',
                'tenant' => $this->getTenantWithStats($tenant),
            ]);

        } catch (\Exception $e) {
            Log::error('Error activating tenant: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to activate tenant'], 500);
        }
    }

    /**
     * Reset tenant admin password
     */
    public function resetAdminPassword(Request $request, $id)
    {
        try {
            $tenant = Tenant::find($id);
            if (!$tenant) {
                return response()->json(['message' => 'Tenant not found'], 404);
            }

            $validated = $request->validate([
                'email' => 'required|email',
                'new_password' => 'required|string|min:8',
            ]);

            $this->switchToTenant($tenant);

            $user = TenantUser::where('email', $validated['email'])->first();
            if (!$user) {
                return response()->json(['message' => 'User not found in tenant'], 404);
            }

            $user->password = Hash::make($validated['new_password']);
            $user->save();

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json([
                'message' => 'Password reset successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error resetting password: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to reset password'], 500);
        }
    }

    /**
     * Get tenant statistics
     */
    public function tenantStats($id)
    {
        try {
            $tenant = Tenant::find($id);
            if (!$tenant) {
                return response()->json(['message' => 'Tenant not found'], 404);
            }

            $this->switchToTenant($tenant);

            $stats = [
                'users' => TenantUser::count(),
                'patients' => Patient::count(),
                'lab_tests' => LabTest::count(),
                'test_results' => TestResult::count(),
            ];

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json([
                'tenant' => $tenant->name,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting tenant stats: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to get tenant stats'], 500);
        }
    }

    /**
     * Get tenant with statistics
     */
    private function getTenantWithStats(Tenant $tenant)
    {
        $stats = [
            'users' => 0,
            'patients' => 0,
            'lab_tests' => 0,
            'test_results' => 0,
        ];

        try {
            $this->switchToTenant($tenant);

            $stats['users'] = TenantUser::count();
            $stats['patients'] = Patient::count();
            $stats['lab_tests'] = LabTest::count();
            $stats['test_results'] = TestResult::count();

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

        } catch (\Exception $e) {
            Log::error('Error getting stats for tenant: ' . $tenant->name);
        }

        return array_merge($tenant->toArray(), ['stats' => $stats]);
    }

    /**
     * Setup a new tenant
     */
    private function setupTenant(Tenant $tenant, array $data)
    {
        try {
            $this->switchToTenant($tenant);

            \Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            $user = TenantUser::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'role' => 'admin',
                'is_active' => true,
            ]);

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

        } catch (\Exception $e) {
            Log::error('Error setting up tenant: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Switch to tenant database
     */
    private function switchToTenant(Tenant $tenant): void
    {
        $host = Config::get('database.connections.landlord.host', env('DB_HOST', '127.0.0.1'));
        $port = Config::get('database.connections.landlord.port', env('DB_PORT', '3306'));
        $username = Config::get('database.connections.landlord.username', env('DB_USERNAME', 'root'));
        $password = Config::get('database.connections.landlord.password', env('DB_PASSWORD', ''));

        Config::set('database.connections.tenant', [
            'driver' => 'mysql',
            'host' => $host,
            'port' => $port,
            'database' => $tenant->database,
            'username' => $username,
            'password' => $password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        DB::purge('tenant');
        DB::connection('tenant')->reconnect();
        Config::set('database.default', 'tenant');
        $tenant->makeCurrent();
    }
}