<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * List all users across all tenants (God Mode)
     */
    public function index(Request $request)
    {
        try {
            $allUsers = [];
            $tenants = Tenant::all();

            foreach ($tenants as $tenant) {
                try {
                    $this->switchToTenant($tenant);
                    
                    $users = TenantUser::all();
                    
                    foreach ($users as $user) {
                        $userArray = $user->toArray();
                        $userArray['tenant_name'] = $tenant->name;
                        $userArray['tenant_domain'] = $tenant->domain;
                        $allUsers[] = $userArray;
                    }
                } catch (\Exception $e) {
                    Log::error('Error fetching users from tenant: ' . $tenant->name);
                }
            }

            // Switch back to landlord
            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $allUsers = array_filter($allUsers, function($user) use ($search) {
                    return stripos($user['name'], $search) !== false || 
                           stripos($user['email'], $search) !== false;
                });
            }

            // Sort by created_at
            usort($allUsers, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            // Paginate results
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
            $total = count($allUsers);
            $offset = ($page - 1) * $perPage;
            $userData = array_slice($allUsers, $offset, $perPage);

            return response()->json([
                'users' => [
                    'data' => $userData,
                    'total' => $total,
                    'per_page' => (int)$perPage,
                    'current_page' => (int)$page,
                    'last_page' => ceil($total / $perPage),
                ],
                'total_tenants' => $tenants->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching all users: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch users'], 500);
        }
    }

    /**
     * Get a single user from any tenant (God Mode)
     */
    public function show($id)
    {
        try {
            $tenants = Tenant::all();
            
            foreach ($tenants as $tenant) {
                try {
                    $this->switchToTenant($tenant);
                    
                    $user = TenantUser::where('id', $id)->first();
                    if ($user) {
                        $userArray = $user->toArray();
                        $userArray['tenant_name'] = $tenant->name;
                        $userArray['tenant_domain'] = $tenant->domain;
                        
                        // Switch back to landlord
                        Config::set('database.default', 'landlord');
                        DB::connection('landlord')->reconnect();
                        
                        return response()->json($userArray);
                    }
                } catch (\Exception $e) {
                    Log::error('Error searching tenant: ' . $tenant->name);
                }
            }

            // Switch back to landlord
            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json(['message' => 'User not found'], 404);

        } catch (\Exception $e) {
            Log::error('Error finding user: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to find user'], 500);
        }
    }

    /**
     * Create a user in any tenant (God Mode)
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'password' => 'required|string|min:8',
                'role' => 'required|string|in:superadmin,admin,lab_technician,receptionist,doctor',
                'tenant_domain' => 'required|string|exists:tenants,domain',
                'is_active' => 'sometimes|boolean',
            ]);

            // Find tenant
            $tenant = Tenant::where('domain', $validated['tenant_domain'])->first();
            if (!$tenant) {
                return response()->json(['message' => 'Tenant not found'], 404);
            }

            // Switch to tenant
            $this->switchToTenant($tenant);

            // Check if email already exists in this tenant
            $existingUser = TenantUser::where('email', $validated['email'])->first();
            if ($existingUser) {
                return response()->json([
                    'message' => 'User with this email already exists in this tenant',
                ], 422);
            }

            // Create user
            $user = TenantUser::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Switch back to landlord
            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json([
                'message' => 'User created successfully',
                'user' => $user,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create user'], 500);
        }
    }

    /**
     * Update any user in any tenant (God Mode)
     */
    public function update(Request $request, $id)
    {
        try {
            $tenant = null;
            $user = null;

            // Find user across all tenants
            $tenants = Tenant::all();
            foreach ($tenants as $t) {
                try {
                    $this->switchToTenant($t);
                    
                    $found = TenantUser::where('id', $id)->first();
                    if ($found) {
                        $user = $found;
                        $tenant = $t;
                        break;
                    }
                } catch (\Exception $e) {
                    Log::error('Error searching tenant: ' . $t->name);
                }
            }

            if (!$user) {
                Config::set('database.default', 'landlord');
                DB::connection('landlord')->reconnect();
                return response()->json(['message' => 'User not found'], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => ['sometimes', 'string', 'email', 'max:255'],
                'role' => 'sometimes|string|in:superadmin,admin,lab_technician,receptionist,doctor',
                'is_active' => 'sometimes|boolean',
            ]);

            // Check email uniqueness within tenant
            if (isset($validated['email']) && $validated['email'] !== $user->email) {
                $existingUser = TenantUser::where('email', $validated['email'])
                    ->where('id', '!=', $user->id)
                    ->first();
                if ($existingUser) {
                    return response()->json([
                        'message' => 'Email already exists in this tenant',
                    ], 422);
                }
            }

            // Update user
            $user->update($validated);

            // Switch back to landlord
            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json([
                'message' => 'User updated successfully',
                'user' => $user,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update user'], 500);
        }
    }

    /**
     * Delete any user in any tenant (God Mode)
     */
    public function destroy($id)
    {
        try {
            $tenant = null;
            $user = null;

            // Find user across all tenants
            $tenants = Tenant::all();
            foreach ($tenants as $t) {
                try {
                    $this->switchToTenant($t);
                    
                    $found = TenantUser::where('id', $id)->first();
                    if ($found) {
                        $user = $found;
                        $tenant = $t;
                        break;
                    }
                } catch (\Exception $e) {
                    Log::error('Error searching tenant: ' . $t->name);
                }
            }

            if (!$user) {
                Config::set('database.default', 'landlord');
                DB::connection('landlord')->reconnect();
                return response()->json(['message' => 'User not found'], 404);
            }

            // Prevent deleting superadmin users (safety)
            if ($user->role === 'superadmin') {
                $superadminCount = TenantUser::where('role', 'superadmin')->count();
                if ($superadminCount <= 1) {
                    return response()->json([
                        'message' => 'Cannot delete the last superadmin user',
                    ], 422);
                }
            }

            $user->delete();

            // Switch back to landlord
            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json([
                'message' => 'User deleted successfully',
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete user'], 500);
        }
    }

    /**
     * Reset user password (God Mode)
     */
    public function resetPassword(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'new_password' => 'required|string|min:8',
            ]);

            $tenant = null;
            $user = null;

            // Find user across all tenants
            $tenants = Tenant::all();
            foreach ($tenants as $t) {
                try {
                    $this->switchToTenant($t);
                    
                    $found = TenantUser::where('id', $id)->first();
                    if ($found) {
                        $user = $found;
                        $tenant = $t;
                        break;
                    }
                } catch (\Exception $e) {
                    Log::error('Error searching tenant: ' . $t->name);
                }
            }

            if (!$user) {
                Config::set('database.default', 'landlord');
                DB::connection('landlord')->reconnect();
                return response()->json(['message' => 'User not found'], 404);
            }

            $user->password = Hash::make($validated['new_password']);
            $user->save();

            // Switch back to landlord
            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json([
                'message' => 'Password reset successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
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
     * Assign role to a user (God Mode)
     */
    public function assignRole(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'role' => 'required|string|in:superadmin,admin,lab_technician,receptionist,doctor',
            ]);

            $tenant = null;
            $user = null;

            // Find user across all tenants
            $tenants = Tenant::all();
            foreach ($tenants as $t) {
                try {
                    $this->switchToTenant($t);
                    
                    $found = TenantUser::where('id', $id)->first();
                    if ($found) {
                        $user = $found;
                        $tenant = $t;
                        break;
                    }
                } catch (\Exception $e) {
                    Log::error('Error searching tenant: ' . $t->name);
                }
            }

            if (!$user) {
                Config::set('database.default', 'landlord');
                DB::connection('landlord')->reconnect();
                return response()->json(['message' => 'User not found'], 404);
            }

            $user->role = $validated['role'];
            $user->save();

            // Switch back to landlord
            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json([
                'message' => 'Role assigned successfully',
                'user' => $user,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error assigning role: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to assign role'], 500);
        }
    }

    /**
     * Get all available roles
     */
    public function getRoles()
    {
        return response()->json([
            'roles' => [
                'superadmin' => 'Super Administrator - Full system access',
                'admin' => 'Administrator - Manage all lab operations',
                'lab_technician' => 'Lab Technician - Perform and enter test results',
                'receptionist' => 'Receptionist - Register patients and manage appointments',
                'doctor' => 'Doctor - View patient data and approve results',
            ],
        ]);
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