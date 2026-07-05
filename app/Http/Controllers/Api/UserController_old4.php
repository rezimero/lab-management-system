<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * List all users with proper tenant isolation
     * - Superadmin: sees ALL users from ALL tenants
     * - Regular users: sees ONLY users in their own tenant
     * - Superadmin is completely hidden from non-superadmin users
     */
    public function index(Request $request)
    {
        try {
            // Get the current tenant from the middleware
            $currentTenant = Tenant::current();
            
            if (!$currentTenant) {
                Log::warning('No tenant context set in UserController@index');
                return response()->json([
                    'users' => ['data' => [], 'total' => 0],
                    'available_roles' => $this->getAvailableRoles('guest'),
                    'current_user_role' => 'guest',
                    'is_superadmin' => false,
                    'tenant_name' => null,
                ]);
            }

            // IMPORTANT: Get user from session (set during login)
            $tenantUserId = session('tenant_user_id');
            $tenantUserRole = session('tenant_user_role');
            $tenantUserEmail = session('tenant_user_email');
            
            // If session doesn't have the user, try to get from database
            if (!$tenantUserId) {
                $authUser = $request->user();
                if ($authUser) {
                    // Fetch from tenant database
                    $this->ensureTenantConnection($currentTenant);
                    $currentUser = User::where('id', $authUser->id)->first();
                    
                    if ($currentUser) {
                        $tenantUserId = $currentUser->id;
                        $tenantUserRole = $currentUser->role;
                        $tenantUserEmail = $currentUser->email;
                        
                        // Store in session for future requests
                        session(['tenant_user_id' => $tenantUserId]);
                        session(['tenant_user_role' => $tenantUserRole]);
                        session(['tenant_user_email' => $tenantUserEmail]);
                    }
                }
            }
            
            if (!$tenantUserId) {
                Log::warning('No user found for tenant: ' . $currentTenant->name);
                return response()->json([
                    'users' => ['data' => [], 'total' => 0],
                    'available_roles' => $this->getAvailableRoles('guest'),
                    'current_user_role' => 'guest',
                    'is_superadmin' => false,
                    'tenant_name' => $currentTenant->name,
                ]);
            }

            // Use the role from session (which came from the tenant database)
            $isSuperadmin = $tenantUserRole === 'superadmin';
            $userData = [];
            $total = 0;
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);

            Log::info('UserController@index - User: ' . $tenantUserEmail . ' Role: ' . $tenantUserRole . ' Tenant: ' . $currentTenant->name);

            // Ensure we're on the correct tenant connection
            $this->ensureTenantConnection($currentTenant);

            if ($isSuperadmin) {
                // SUPERADMIN: Query ALL tenants to get ALL users
                Log::info('Superadmin fetching all users from all tenants');
                
                $tenants = Tenant::all();
                $allUsers = [];
                
                foreach ($tenants as $tenant) {
                    $this->ensureTenantConnection($tenant);
                    $users = User::all();
                    
                    foreach ($users as $user) {
                        $userArray = $user->toArray();
                        $userArray['tenant_name'] = $tenant->name;
                        $userArray['tenant_domain'] = $tenant->domain;
                        $allUsers[] = $userArray;
                    }
                }
                
                $this->ensureTenantConnection($currentTenant);
                
                if ($request->has('search') && !empty($request->search)) {
                    $search = $request->search;
                    $allUsers = array_filter($allUsers, function($user) use ($search) {
                        return stripos($user['name'], $search) !== false || 
                               stripos($user['email'], $search) !== false;
                    });
                }
                
                usort($allUsers, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
                
                $total = count($allUsers);
                $offset = ($page - 1) * $perPage;
                $userData = array_slice($allUsers, $offset, $perPage);
                
                Log::info('Superadmin found ' . $total . ' users across all tenants');
                
            } else {
                // REGULAR USER: Query ONLY the current tenant
                Log::info('Regular user fetching users from current tenant: ' . $currentTenant->name);
                
                $this->ensureTenantConnection($currentTenant);
                
                $query = User::query();
                
                if ($request->has('search') && !empty($request->search)) {
                    $search = $request->search;
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
                }
                
                $total = $query->count();
                $offset = ($page - 1) * $perPage;
                $users = $query->orderBy('created_at', 'desc')
                               ->offset($offset)
                               ->limit($perPage)
                               ->get();
                
                foreach ($users as $user) {
                    $userArray = $user->toArray();
                    $userArray['tenant_name'] = $currentTenant->name;
                    $userArray['tenant_domain'] = $currentTenant->domain;
                    $userData[] = $userArray;
                }
                
                Log::info('Found ' . $total . ' users in tenant: ' . $currentTenant->name);
            }

            $availableRoles = $this->getAvailableRoles($tenantUserRole);
            
            return response()->json([
                'users' => [
                    'data' => $userData,
                    'total' => $total,
                    'per_page' => (int)$perPage,
                    'current_page' => (int)$page,
                    'last_page' => ceil($total / $perPage),
                ],
                'available_roles' => $availableRoles,
                'current_user_role' => $tenantUserRole,
                'is_superadmin' => $tenantUserRole === 'superadmin',
                'tenant_name' => $currentTenant->name,
                'user_email' => $tenantUserEmail,
                'user_id' => $tenantUserId,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in UserController@index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'users' => ['data' => [], 'total' => 0],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ensure the tenant connection is properly set
     */
    private function ensureTenantConnection(Tenant $tenant): void
    {
        // Get database credentials from config
        $host = config('database.connections.landlord.host', env('DB_HOST', '127.0.0.1'));
        $port = config('database.connections.landlord.port', env('DB_PORT', '3306'));
        $username = config('database.connections.landlord.username', env('DB_USERNAME', 'root'));
        $password = config('database.connections.landlord.password', env('DB_PASSWORD', ''));

        // Set the database name for the tenant connection
        config([
            'database.connections.tenant.database' => $tenant->database,
            'database.connections.tenant.host' => $host,
            'database.connections.tenant.port' => $port,
            'database.connections.tenant.username' => $username,
            'database.connections.tenant.password' => $password,
        ]);

        // Purge and reconnect to force the new connection
        DB::purge('tenant');
        DB::connection('tenant')->reconnect();
        
        // Also set the default connection to tenant
        config(['database.default' => 'tenant']);
        
        // Make tenant current
        $tenant->makeCurrent();
    }

    /**
     * Get a single user (tenant isolation + superadmin protection)
     */
    public function show($id)
    {
        try {
            $authUser = auth()->user();
            $currentTenant = Tenant::current();
            
            if (!$currentTenant) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Get user role from session
            $tenantUserRole = session('tenant_user_role');
            
            if (!$tenantUserRole) {
                // Fallback: fetch from tenant database
                $this->ensureTenantConnection($currentTenant);
                $currentUser = User::where('id', $authUser->id)->first();
                if ($currentUser) {
                    $tenantUserRole = $currentUser->role;
                }
            }

            $isSuperadmin = $tenantUserRole === 'superadmin';

            if ($isSuperadmin) {
                // SUPERADMIN: Search across all tenants
                $tenants = Tenant::all();
                foreach ($tenants as $tenant) {
                    $this->ensureTenantConnection($tenant);
                    
                    $user = User::where('id', $id)->first();
                    
                    if ($user) {
                        $userArray = $user->toArray();
                        $userArray['tenant_name'] = $tenant->name;
                        $userArray['tenant_domain'] = $tenant->domain;
                        return response()->json($userArray);
                    }
                }
                return response()->json(['message' => 'User not found'], 404);
                
            } else {
                // REGULAR USER: Only search in current tenant
                $this->ensureTenantConnection($currentTenant);
                
                $user = User::where('id', $id)->first();
                
                if (!$user) {
                    return response()->json(['message' => 'User not found'], 404);
                }
                
                return response()->json($user);
            }
            
        } catch (\Exception $e) {
            Log::error('Error in UserController@show: ' . $e->getMessage());
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    /**
     * Create a new user
     */
    public function store(Request $request)
    {
        try {
            $authUser = $request->user();
            $currentTenant = Tenant::current();
            
            if (!$currentTenant) {
                return response()->json(['message' => 'No tenant context'], 400);
            }
            
            // Get user role from session
            $tenantUserRole = session('tenant_user_role');
            
            if (!$tenantUserRole) {
                // Fallback: fetch from tenant database
                $this->ensureTenantConnection($currentTenant);
                $currentUser = User::where('id', $authUser->id)->first();
                if ($currentUser) {
                    $tenantUserRole = $currentUser->role;
                }
            }
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|string|in:superadmin,admin,lab_technician,receptionist,doctor',
                'is_active' => 'sometimes|boolean',
            ]);

            // Only superadmin can create superadmin
            if ($validated['role'] === 'superadmin' && $tenantUserRole !== 'superadmin') {
                return response()->json([
                    'message' => 'Unauthorized to create superadmin user',
                ], 403);
            }

            // Ensure we're on the correct tenant
            $this->ensureTenantConnection($currentTenant);

            // Create user using the User model
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            return response()->json([
                'message' => 'User created successfully',
                'user' => $user,
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Error in UserController@store: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create user'], 500);
        }
    }

    /**
     * Update a user
     */
    public function update(Request $request, $id)
    {
        try {
            $authUser = $request->user();
            $currentTenant = Tenant::current();
            
            if (!$currentTenant) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Get user role from session
            $tenantUserRole = session('tenant_user_role');
            
            if (!$tenantUserRole) {
                // Fallback: fetch from tenant database
                $this->ensureTenantConnection($currentTenant);
                $currentUser = User::where('id', $authUser->id)->first();
                if ($currentUser) {
                    $tenantUserRole = $currentUser->role;
                }
            }

            $isSuperadmin = $tenantUserRole === 'superadmin';

            // Find the user
            $user = null;
            $userTenant = null;
            
            if ($isSuperadmin) {
                // SUPERADMIN: Search across all tenants
                $tenants = Tenant::all();
                foreach ($tenants as $tenant) {
                    $this->ensureTenantConnection($tenant);
                    
                    $found = User::where('id', $id)->first();
                    
                    if ($found) {
                        $user = $found;
                        $userTenant = $tenant;
                        break;
                    }
                }
            } else {
                // REGULAR USER: Only search in current tenant
                $this->ensureTenantConnection($currentTenant);
                $user = User::where('id', $id)->first();
                $userTenant = $currentTenant;
            }
            
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }
            
            // Superadmin protection - completely hide from non-superadmins
            if ($user->role === 'superadmin' && !$isSuperadmin) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Prevent self-modification of superadmin
            if ($user->id == $authUser->id && $user->role === 'superadmin') {
                return response()->json([
                    'message' => 'Cannot modify your own superadmin account',
                ], 403);
            }

            // Switch to the correct tenant for update
            if ($isSuperadmin && $userTenant) {
                $this->ensureTenantConnection($userTenant);
            } else {
                $this->ensureTenantConnection($currentTenant);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'password' => 'sometimes|string|min:8',
                'role' => 'sometimes|string|in:superadmin,admin,lab_technician,receptionist,doctor',
                'is_active' => 'sometimes|boolean',
            ]);

            // Only superadmin can assign superadmin role
            if (isset($validated['role']) && $validated['role'] === 'superadmin' && !$isSuperadmin) {
                return response()->json([
                    'message' => 'Only superadmin can assign superadmin role',
                ], 403);
            }

            // Prepare update data
            $updateData = [];
            if (isset($validated['name'])) $updateData['name'] = $validated['name'];
            if (isset($validated['email'])) $updateData['email'] = $validated['email'];
            if (isset($validated['role'])) $updateData['role'] = $validated['role'];
            if (isset($validated['is_active'])) $updateData['is_active'] = $validated['is_active'];
            if (isset($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }
            
            $user->update($updateData);
            
            // Refresh user to get updated data
            $updatedUser = $user->fresh();

            return response()->json([
                'message' => 'User updated successfully',
                'user' => $updatedUser,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in UserController@update: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update user'], 500);
        }
    }

    /**
     * Delete a user
     */
    public function destroy($id)
    {
        try {
            $authUser = auth()->user();
            $currentTenant = Tenant::current();
            
            if (!$currentTenant) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Get user role from session
            $tenantUserRole = session('tenant_user_role');
            
            if (!$tenantUserRole) {
                // Fallback: fetch from tenant database
                $this->ensureTenantConnection($currentTenant);
                $currentUser = User::where('id', $authUser->id)->first();
                if ($currentUser) {
                    $tenantUserRole = $currentUser->role;
                }
            }

            $isSuperadmin = $tenantUserRole === 'superadmin';

            // Find the user
            $user = null;
            $userTenant = null;
            
            if ($isSuperadmin) {
                // SUPERADMIN: Search across all tenants
                $tenants = Tenant::all();
                foreach ($tenants as $tenant) {
                    $this->ensureTenantConnection($tenant);
                    
                    $found = User::where('id', $id)->first();
                    
                    if ($found) {
                        $user = $found;
                        $userTenant = $tenant;
                        break;
                    }
                }
            } else {
                // REGULAR USER: Only search in current tenant
                $this->ensureTenantConnection($currentTenant);
                $user = User::where('id', $id)->first();
                $userTenant = $currentTenant;
            }
            
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }
            
            // Superadmin protection - completely hide from non-superadmins
            if ($user->role === 'superadmin' && !$isSuperadmin) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Prevent deleting the last superadmin
            if ($user->role === 'superadmin') {
                $superadminCount = User::where('role', 'superadmin')->count();
                if ($superadminCount <= 1) {
                    return response()->json([
                        'message' => 'Cannot delete the last superadmin user',
                    ], 422);
                }
            }

            // Prevent deleting yourself
            if ($user->id == $authUser->id) {
                return response()->json([
                    'message' => 'Cannot delete your own account',
                ], 422);
            }

            // Switch to the correct tenant for deletion
            if ($isSuperadmin && $userTenant) {
                $this->ensureTenantConnection($userTenant);
            } else {
                $this->ensureTenantConnection($currentTenant);
            }

            $user->delete();

            return response()->json([
                'message' => 'User deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in UserController@destroy: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete user'], 500);
        }
    }

    /**
     * Assign a role to a user
     */
    public function assignRole(Request $request, $id)
    {
        try {
            $authUser = auth()->user();
            $currentTenant = Tenant::current();
            
            if (!$currentTenant) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Get user role from session
            $tenantUserRole = session('tenant_user_role');
            
            if (!$tenantUserRole) {
                // Fallback: fetch from tenant database
                $this->ensureTenantConnection($currentTenant);
                $currentUser = User::where('id', $authUser->id)->first();
                if ($currentUser) {
                    $tenantUserRole = $currentUser->role;
                }
            }

            $isSuperadmin = $tenantUserRole === 'superadmin';

            // Find the user
            $user = null;
            $userTenant = null;
            
            if ($isSuperadmin) {
                $tenants = Tenant::all();
                foreach ($tenants as $tenant) {
                    $this->ensureTenantConnection($tenant);
                    
                    $found = User::where('id', $id)->first();
                    
                    if ($found) {
                        $user = $found;
                        $userTenant = $tenant;
                        break;
                    }
                }
            } else {
                $this->ensureTenantConnection($currentTenant);
                $user = User::where('id', $id)->first();
                $userTenant = $currentTenant;
            }
            
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }
            
            // Superadmin protection - completely hide from non-superadmins
            if ($user->role === 'superadmin' && !$isSuperadmin) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $validated = $request->validate([
                'role' => 'required|string|in:superadmin,admin,lab_technician,receptionist,doctor',
            ]);

            // Only superadmin can assign superadmin role
            if ($validated['role'] === 'superadmin' && !$isSuperadmin) {
                return response()->json([
                    'message' => 'Only superadmin can assign superadmin role',
                ], 403);
            }

            // Switch to the correct tenant for update
            if ($isSuperadmin && $userTenant) {
                $this->ensureTenantConnection($userTenant);
            } else {
                $this->ensureTenantConnection($currentTenant);
            }

            $user->update([
                'role' => $validated['role'],
            ]);
            
            $updatedUser = $user->fresh();

            return response()->json([
                'message' => 'Role assigned successfully',
                'user' => $updatedUser,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in UserController@assignRole: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to assign role'], 500);
        }
    }

    /**
     * Get all available roles
     */
    public function getRoles()
    {
        $currentUser = auth()->user();
        $tenantUserRole = session('tenant_user_role');
        
        if (!$tenantUserRole && $currentUser) {
            $currentTenant = Tenant::current();
            if ($currentTenant) {
                $this->ensureTenantConnection($currentTenant);
                $user = User::where('id', $currentUser->id)->first();
                if ($user) {
                    $tenantUserRole = $user->role;
                }
            }
        }
        
        $roles = $this->getAvailableRoles($tenantUserRole);
        
        return response()->json([
            'roles' => $roles,
        ]);
    }

    /**
     * Get available roles list based on user role
     */
    private function getAvailableRoles($currentUserRole = null)
    {
        $allRoles = [
            'superadmin' => 'Super Administrator - Full system access',
            'admin' => 'Administrator - Manage all lab operations',
            'lab_technician' => 'Lab Technician - Perform and enter test results',
            'receptionist' => 'Receptionist - Register patients and manage appointments',
            'doctor' => 'Doctor - View patient data and approve results',
        ];
        
        // If not superadmin, completely remove superadmin from available roles
        if ($currentUserRole !== 'superadmin') {
            unset($allRoles['superadmin']);
        }
        
        return $allRoles;
    }
}