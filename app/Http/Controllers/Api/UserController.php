<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User as TenantUser;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Check if the current user is a Superadmin (God Mode)
     */
    private function isSuperAdmin(): bool
    {
        return app()->bound('superadmin.override') && app('superadmin.override') === true;
    }

    /**
     * List all users
     * - Superadmin: Sees ALL users from ALL tenants
     * - Regular users: Sees ONLY users in their own tenant
     */
    public function index(Request $request)
    {
        try {
            // Check if user is superadmin
            $isSuperAdmin = $this->isSuperAdmin();
            
            // If Superadmin, return ALL users from ALL tenants
            if ($isSuperAdmin) {
                Log::info('Superadmin fetching all users from all tenants');
                
                $allUsers = collect();
                $tenants = Tenant::all();
                
                foreach ($tenants as $tenant) {
                    try {
                        // Switch to tenant database
                        Config::set('database.connections.tenant.database', $tenant->database);
                        DB::purge('tenant');
                        DB::connection('tenant')->reconnect();
                        Config::set('database.default', 'tenant');
                        $tenant->makeCurrent();
                        
                        // Get users from this tenant
                        $users = TenantUser::all();
                        
                        foreach ($users as $user) {
                            $userArray = $user->toArray();
                            $userArray['tenant_name'] = $tenant->name;
                            $userArray['tenant_domain'] = $tenant->domain;
                            $allUsers->push($userArray);
                        }
                    } catch (\Exception $e) {
                        Log::error('Error fetching users from tenant: ' . $tenant->name . ' - ' . $e->getMessage());
                    }
                }
                
                // Switch back to landlord connection
                Config::set('database.default', 'landlord');
                DB::connection('landlord')->reconnect();
                
                // Apply search filter
                if ($request->has('search') && !empty($request->search)) {
                    $search = $request->search;
                    $allUsers = $allUsers->filter(function($user) use ($search) {
                        return stripos($user['name'], $search) !== false || 
                               stripos($user['email'], $search) !== false;
                    });
                }
                
                // Sort by created_at
                $allUsers = $allUsers->sortByDesc('created_at')->values();
                
                // Paginate results
                $perPage = $request->input('per_page', 15);
                $page = $request->input('page', 1);
                $total = $allUsers->count();
                $offset = ($page - 1) * $perPage;
                $userData = $allUsers->slice($offset, $perPage)->values()->toArray();
                
                return response()->json([
                    'users' => [
                        'data' => $userData,
                        'total' => $total,
                        'per_page' => (int)$perPage,
                        'current_page' => (int)$page,
                        'last_page' => ceil($total / $perPage),
                    ],
                    'available_roles' => $this->getAvailableRoles('superadmin'),
                    'current_user_role' => 'superadmin',
                    'is_superadmin' => true,
                    'tenant_name' => 'All Tenants (God Mode)',
                    'total_tenants' => $tenants->count(),
                ]);
            }
            
            // ============================================================
            // REGULAR USER: Only get users from CURRENT tenant
            // ============================================================
            
            $tenant = Tenant::current();
            
            if (!$tenant) {
                return response()->json([
                    'users' => ['data' => [], 'total' => 0],
                    'current_user_role' => 'guest',
                    'is_superadmin' => false,
                    'tenant_name' => null,
                ]);
            }

            // Force tenant connection
            Config::set('database.default', 'tenant');
            DB::connection('tenant')->reconnect();

            // Get user from session (set during login)
            $userEmail = session('tenant_user_email');
            $userRole = session('tenant_user_role');
            $userId = session('tenant_user_id');
            
            // If session doesn't have user data, get from tenant database
            if (!$userEmail) {
                $authUser = $request->user();
                if ($authUser) {
                    $user = TenantUser::where('id', $authUser->id)->first();
                    if ($user) {
                        $userEmail = $user->email;
                        $userRole = $user->role;
                        $userId = $user->id;
                        session([
                            'tenant_user_id' => $userId,
                            'tenant_user_email' => $userEmail,
                            'tenant_user_role' => $userRole,
                        ]);
                    }
                }
            }
            
            // If still no user, get the first user in this tenant
            if (!$userEmail) {
                $fallbackUser = TenantUser::first();
                if ($fallbackUser) {
                    $userEmail = $fallbackUser->email;
                    $userRole = $fallbackUser->role;
                    $userId = $fallbackUser->id;
                    Log::warning('Using fallback user: ' . $userEmail);
                } else {
                    return response()->json([
                        'users' => ['data' => [], 'total' => 0],
                        'current_user_role' => 'guest',
                        'is_superadmin' => false,
                        'tenant_name' => $tenant->name,
                        'error' => 'No users found in tenant',
                    ]);
                }
            }

            $isSuperadmin = ($userRole === 'superadmin');
            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);

            Log::info('UserController@index - Email: ' . $userEmail . ' Role: ' . $userRole . ' Tenant: ' . $tenant->name);

            // Get users from current tenant ONLY
            $query = TenantUser::query();
            
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
            
            $userData = [];
            foreach ($users as $user) {
                $userArray = $user->toArray();
                $userArray['tenant_name'] = $tenant->name;
                $userArray['tenant_domain'] = $tenant->domain;
                $userData[] = $userArray;
            }

            $availableRoles = $this->getAvailableRoles($userRole);

            return response()->json([
                'users' => [
                    'data' => $userData,
                    'total' => $total,
                    'per_page' => (int)$perPage,
                    'current_page' => (int)$page,
                    'last_page' => ceil($total / $perPage),
                ],
                'available_roles' => $availableRoles,
                'current_user_role' => $userRole,
                'is_superadmin' => $isSuperadmin,
                'tenant_name' => $tenant->name,
                'user_email' => $userEmail,
                'user_id' => $userId,
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
     * Show a single user
     * - Superadmin: Can see any user from any tenant
     * - Regular users: Only see users in their own tenant
     */
    public function show($id)
    {
        try {
            $isSuperAdmin = $this->isSuperAdmin();
            
            if ($isSuperAdmin) {
                // Superadmin: Search across all tenants
                $tenants = Tenant::all();
                foreach ($tenants as $tenant) {
                    try {
                        Config::set('database.connections.tenant.database', $tenant->database);
                        DB::purge('tenant');
                        DB::connection('tenant')->reconnect();
                        $tenant->makeCurrent();
                        
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
                        Log::error('Error searching tenant: ' . $e->getMessage());
                    }
                }
                
                // Switch back to landlord
                Config::set('database.default', 'landlord');
                DB::connection('landlord')->reconnect();
                
                return response()->json(['message' => 'User not found'], 404);
            }
            
            // Regular user: Only search in current tenant
            $tenant = Tenant::current();
            if (!$tenant) {
                return response()->json(['message' => 'User not found'], 404);
            }

            Config::set('database.default', 'tenant');
            DB::connection('tenant')->reconnect();

            $user = TenantUser::where('id', $id)->first();
            
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            return response()->json($user);
            
        } catch (\Exception $e) {
            Log::error('Error in UserController@show: ' . $e->getMessage());
            return response()->json(['message' => 'User not found'], 404);
        }
    }

    /**
     * Create a new user
     * - Superadmin: Can create users in ANY tenant
     * - Regular users: Can only create users in their own tenant
     */
    public function store(Request $request)
    {
        try {
            $isSuperAdmin = $this->isSuperAdmin();
            $targetTenant = null;
            
            // If superadmin, they can specify which tenant to create the user in
            if ($isSuperAdmin) {
                $domain = $request->input('tenant_domain') ?? $request->header('X-Tenant-Domain');
                
                if ($domain) {
                    $targetTenant = Tenant::where('domain', $domain)->first();
                    if (!$targetTenant) {
                        return response()->json(['message' => 'Invalid tenant domain'], 422);
                    }
                } else {
                    return response()->json(['message' => 'tenant_domain required for superadmin'], 422);
                }
            } else {
                $targetTenant = Tenant::current();
                if (!$targetTenant) {
                    return response()->json(['message' => 'No tenant context'], 400);
                }
            }

            // Switch to target tenant
            Config::set('database.connections.tenant.database', $targetTenant->database);
            DB::purge('tenant');
            DB::connection('tenant')->reconnect();
            Config::set('database.default', 'tenant');
            $targetTenant->makeCurrent();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|string|in:superadmin,admin,lab_technician,receptionist,doctor',
                'is_active' => 'sometimes|boolean',
            ]);

            // Only superadmin can create superadmin users
            if ($validated['role'] === 'superadmin' && !$isSuperAdmin) {
                return response()->json(['message' => 'Unauthorized to create superadmin user'], 403);
            }

            $user = TenantUser::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Switch back to landlord if superadmin
            if ($isSuperAdmin) {
                Config::set('database.default', 'landlord');
                DB::connection('landlord')->reconnect();
            }

            return response()->json([
                'message' => 'User created successfully',
                'user' => $user,
                'tenant' => [
                    'id' => $targetTenant->id,
                    'name' => $targetTenant->name,
                    'domain' => $targetTenant->domain,
                ],
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Error in UserController@store: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create user: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update a user
     * - Superadmin: Can update ANY user in ANY tenant
     * - Regular users: Can only update users in their own tenant
     */
    public function update(Request $request, $id)
    {
        try {
            $isSuperAdmin = $this->isSuperAdmin();
            $currentTenant = null;
            $user = null;
            
            if ($isSuperAdmin) {
                // Superadmin: Find the user across all tenants
                $tenants = Tenant::all();
                foreach ($tenants as $tenant) {
                    try {
                        Config::set('database.connections.tenant.database', $tenant->database);
                        DB::purge('tenant');
                        DB::connection('tenant')->reconnect();
                        $tenant->makeCurrent();
                        
                        $found = TenantUser::where('id', $id)->first();
                        if ($found) {
                            $user = $found;
                            $currentTenant = $tenant;
                            break;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error searching tenant: ' . $e->getMessage());
                    }
                }
                
                if (!$user) {
                    // Switch back to landlord
                    Config::set('database.default', 'landlord');
                    DB::connection('landlord')->reconnect();
                    return response()->json(['message' => 'User not found'], 404);
                }
            } else {
                // Regular user: Only search in current tenant
                $currentTenant = Tenant::current();
                if (!$currentTenant) {
                    return response()->json(['message' => 'User not found'], 404);
                }
                
                Config::set('database.default', 'tenant');
                DB::connection('tenant')->reconnect();
                $user = TenantUser::where('id', $id)->first();
            }
            
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Superadmin bypasses all checks
            if (!$isSuperAdmin) {
                // Regular users cannot modify superadmin users
                if ($user->role === 'superadmin') {
                    return response()->json(['message' => 'User not found'], 404);
                }
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'password' => 'sometimes|string|min:8',
                'role' => 'sometimes|string|in:superadmin,admin,lab_technician,receptionist,doctor',
                'is_active' => 'sometimes|boolean',
            ]);

            // Only superadmin can assign superadmin role
            if (isset($validated['role']) && $validated['role'] === 'superadmin' && !$isSuperAdmin) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $updateData = [];
            if (isset($validated['name'])) $updateData['name'] = $validated['name'];
            if (isset($validated['email'])) $updateData['email'] = $validated['email'];
            if (isset($validated['role'])) $updateData['role'] = $validated['role'];
            if (isset($validated['is_active'])) $updateData['is_active'] = $validated['is_active'];
            if (isset($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }
            
            $user->update($updateData);

            // Switch back to landlord if superadmin
            if ($isSuperAdmin) {
                Config::set('database.default', 'landlord');
                DB::connection('landlord')->reconnect();
            }

            return response()->json([
                'message' => 'User updated successfully',
                'user' => $user->fresh(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in UserController@update: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update user'], 500);
        }
    }

    /**
     * Delete a user
     * - Superadmin: Can delete ANY user in ANY tenant
     * - Regular users: Can only delete users in their own tenant
     */
    public function destroy($id)
    {
        try {
            $isSuperAdmin = $this->isSuperAdmin();
            $currentTenant = null;
            $user = null;
            
            if ($isSuperAdmin) {
                // Superadmin: Find the user across all tenants
                $tenants = Tenant::all();
                foreach ($tenants as $tenant) {
                    try {
                        Config::set('database.connections.tenant.database', $tenant->database);
                        DB::purge('tenant');
                        DB::connection('tenant')->reconnect();
                        $tenant->makeCurrent();
                        
                        $found = TenantUser::where('id', $id)->first();
                        if ($found) {
                            $user = $found;
                            $currentTenant = $tenant;
                            break;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error searching tenant: ' . $e->getMessage());
                    }
                }
                
                if (!$user) {
                    Config::set('database.default', 'landlord');
                    DB::connection('landlord')->reconnect();
                    return response()->json(['message' => 'User not found'], 404);
                }
            } else {
                // Regular user: Only search in current tenant
                $currentTenant = Tenant::current();
                if (!$currentTenant) {
                    return response()->json(['message' => 'User not found'], 404);
                }
                
                Config::set('database.default', 'tenant');
                DB::connection('tenant')->reconnect();
                $user = TenantUser::where('id', $id)->first();
            }
            
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Superadmin bypasses all checks
            if (!$isSuperAdmin) {
                // Regular users cannot delete superadmin users
                if ($user->role === 'superadmin') {
                    return response()->json(['message' => 'User not found'], 404);
                }
                
                // Regular users cannot delete themselves
                $currentUserEmail = session('tenant_user_email');
                if ($user->email === $currentUserEmail) {
                    return response()->json(['message' => 'Cannot delete your own account'], 422);
                }
            }

            $user->delete();

            // Switch back to landlord if superadmin
            if ($isSuperAdmin) {
                Config::set('database.default', 'landlord');
                DB::connection('landlord')->reconnect();
            }

            return response()->json(['message' => 'User deleted successfully']);
            
        } catch (\Exception $e) {
            Log::error('Error in UserController@destroy: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete user'], 500);
        }
    }

    /**
     * Assign a role to a user
     * - Superadmin: Can assign ANY role to ANY user
     * - Regular users: Limited to their tenant
     */
    public function assignRole(Request $request, $id)
    {
        try {
            $isSuperAdmin = $this->isSuperAdmin();
            $currentTenant = null;
            $user = null;
            
            if ($isSuperAdmin) {
                $tenants = Tenant::all();
                foreach ($tenants as $tenant) {
                    try {
                        Config::set('database.connections.tenant.database', $tenant->database);
                        DB::purge('tenant');
                        DB::connection('tenant')->reconnect();
                        $tenant->makeCurrent();
                        
                        $found = TenantUser::where('id', $id)->first();
                        if ($found) {
                            $user = $found;
                            $currentTenant = $tenant;
                            break;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error searching tenant: ' . $e->getMessage());
                    }
                }
                
                if (!$user) {
                    Config::set('database.default', 'landlord');
                    DB::connection('landlord')->reconnect();
                    return response()->json(['message' => 'User not found'], 404);
                }
            } else {
                $currentTenant = Tenant::current();
                if (!$currentTenant) {
                    return response()->json(['message' => 'User not found'], 404);
                }
                
                Config::set('database.default', 'tenant');
                DB::connection('tenant')->reconnect();
                $user = TenantUser::where('id', $id)->first();
            }
            
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Superadmin bypasses all checks
            if (!$isSuperAdmin) {
                if ($user->role === 'superadmin') {
                    return response()->json(['message' => 'User not found'], 404);
                }
            }

            $validated = $request->validate([
                'role' => 'required|string|in:superadmin,admin,lab_technician,receptionist,doctor',
            ]);

            // Only superadmin can assign superadmin role
            if ($validated['role'] === 'superadmin' && !$isSuperAdmin) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $user->update(['role' => $validated['role']]);

            if ($isSuperAdmin) {
                Config::set('database.default', 'landlord');
                DB::connection('landlord')->reconnect();
            }

            return response()->json([
                'message' => 'Role assigned successfully',
                'user' => $user->fresh(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error in UserController@assignRole: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to assign role'], 500);
        }
    }

    /**
     * Get all available roles
     */
    public function getRoles(Request $request)
    {
        $isSuperAdmin = $this->isSuperAdmin();
        
        $allRoles = [
            'superadmin' => 'Super Administrator - Full system access',
            'admin' => 'Administrator - Manage all lab operations',
            'lab_technician' => 'Lab Technician - Perform and enter test results',
            'receptionist' => 'Receptionist - Register patients and manage appointments',
            'doctor' => 'Doctor - View patient data and approve results',
        ];
        
        // Only superadmin can see the superadmin role option
        if (!$isSuperAdmin) {
            unset($allRoles['superadmin']);
        }
        
        return response()->json(['roles' => $allRoles]);
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
        
        if ($currentUserRole !== 'superadmin') {
            unset($allRoles['superadmin']);
        }
        
        return $allRoles;
    }
}