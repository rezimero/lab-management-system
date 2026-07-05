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
            $currentUser = $request->user();
            $currentTenant = Tenant::current();
            $isSuperadmin = $currentUser->role === 'superadmin';
            
            // If no tenant is set, return empty
            if (!$currentTenant) {
                return response()->json([
                    'users' => ['data' => [], 'total' => 0],
                    'available_roles' => $this->getAvailableRoles($currentUser->role),
                    'current_user_role' => $currentUser->role,
                    'is_superadmin' => $isSuperadmin,
                ]);
            }

            $userData = [];
            $total = 0;

            if ($isSuperadmin) {
                // SUPERADMIN: Query ALL tenants to get ALL users
                Log::info('Superadmin fetching all users from all tenants');
                
                $tenants = Tenant::all();
                $allUsers = [];
                
                foreach ($tenants as $tenant) {
                    // Switch to each tenant's database
                    config(['database.connections.tenant.database' => $tenant->database]);
                    DB::purge('tenant');
                    DB::connection('tenant')->reconnect();
                    $tenant->makeCurrent();
                    
                    // Get users from this tenant
                    $users = DB::connection('tenant')->table('users')->get();
                    
                    foreach ($users as $user) {
                        // Add tenant information to each user
                        $userArray = (array) $user;
                        $userArray['tenant_name'] = $tenant->name;
                        $userArray['tenant_domain'] = $tenant->domain;
                        $allUsers[] = $userArray;
                    }
                }
                
                // Apply search filter to all users
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
                $total = count($allUsers);
                $perPage = $request->input('per_page', 15);
                $page = $request->input('page', 1);
                $offset = ($page - 1) * $perPage;
                $userData = array_slice($allUsers, $offset, $perPage);
                
                Log::info('Superadmin found ' . $total . ' users across all tenants');
                
            } else {
                // REGULAR USER: Query ONLY the current tenant
                Log::info('Regular user fetching users from current tenant: ' . $currentTenant->name);
                
                // Ensure we're on the correct tenant connection
                config(['database.connections.tenant.database' => $currentTenant->database]);
                DB::purge('tenant');
                DB::connection('tenant')->reconnect();
                $currentTenant->makeCurrent();
                
                // Build query for current tenant only
                $query = DB::connection('tenant')->table('users');
                
                // IMPORTANT: Superadmin users don't exist in other tenants' databases
                // So we don't need to filter them out - they simply don't exist here
                
                // Apply search filter
                if ($request->has('search') && !empty($request->search)) {
                    $search = $request->search;
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
                }
                
                // Get total count
                $total = $query->count();
                
                // Get paginated results
                $perPage = $request->input('per_page', 15);
                $page = $request->input('page', 1);
                $offset = ($page - 1) * $perPage;
                
                $users = $query->orderBy('created_at', 'desc')
                               ->offset($offset)
                               ->limit($perPage)
                               ->get();
                
                // Convert to array
                foreach ($users as $user) {
                    $userArray = (array) $user;
                    $userArray['tenant_name'] = $currentTenant->name;
                    $userArray['tenant_domain'] = $currentTenant->domain;
                    $userData[] = $userArray;
                }
                
                Log::info('Found ' . $total . ' users in tenant: ' . $currentTenant->name);
            }

            // Get available roles based on user role
            $availableRoles = $this->getAvailableRoles($currentUser->role);
            
            return response()->json([
                'users' => [
                    'data' => $userData,
                    'total' => $total,
                    'per_page' => (int)$perPage,
                    'current_page' => (int)$page,
                    'last_page' => ceil($total / $perPage),
                ],
                'available_roles' => $availableRoles,
                'current_user_role' => $currentUser->role,
                'is_superadmin' => $isSuperadmin,
                'tenant_name' => $currentTenant->name,
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
     * Get a single user (tenant isolation + superadmin protection)
     */
    public function show($id)
    {
        try {
            $currentUser = auth()->user();
            $currentTenant = Tenant::current();
            $isSuperadmin = $currentUser->role === 'superadmin';
            
            if (!$currentTenant) {
                return response()->json(['message' => 'User not found'], 404);
            }

            if ($isSuperadmin) {
                // SUPERADMIN: Search across all tenants
                $tenants = Tenant::all();
                foreach ($tenants as $tenant) {
                    config(['database.connections.tenant.database' => $tenant->database]);
                    DB::purge('tenant');
                    DB::connection('tenant')->reconnect();
                    $tenant->makeCurrent();
                    
                    $user = DB::connection('tenant')
                        ->table('users')
                        ->where('id', $id)
                        ->first();
                    
                    if ($user) {
                        $userArray = (array) $user;
                        $userArray['tenant_name'] = $tenant->name;
                        $userArray['tenant_domain'] = $tenant->domain;
                        return response()->json($userArray);
                    }
                }
                return response()->json(['message' => 'User not found'], 404);
                
            } else {
                // REGULAR USER: Only search in current tenant
                config(['database.connections.tenant.database' => $currentTenant->database]);
                DB::purge('tenant');
                DB::connection('tenant')->reconnect();
                $currentTenant->makeCurrent();
                
                $user = DB::connection('tenant')
                    ->table('users')
                    ->where('id', $id)
                    ->first();
                
                if (!$user) {
                    return response()->json(['message' => 'User not found'], 404);
                }
                
                // Superadmin users don't exist in other tenants, so no need to filter
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
            $currentUser = $request->user();
            $currentTenant = Tenant::current();
            
            if (!$currentTenant) {
                return response()->json(['message' => 'No tenant context'], 400);
            }
            
            // Ensure we're on the correct tenant connection
            config(['database.connections.tenant.database' => $currentTenant->database]);
            DB::purge('tenant');
            DB::connection('tenant')->reconnect();
            $currentTenant->makeCurrent();
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|string|in:superadmin,admin,lab_technician,receptionist,doctor',
                'is_active' => 'sometimes|boolean',
            ]);

            // Only superadmin can create superadmin
            if ($validated['role'] === 'superadmin' && $currentUser->role !== 'superadmin') {
                return response()->json([
                    'message' => 'Unauthorized to create superadmin user',
                ], 403);
            }

            // Create user in tenant database
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'is_active' => $validated['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            $userId = DB::connection('tenant')->table('users')->insertGetId($userData);
            $user = DB::connection('tenant')->table('users')->where('id', $userId)->first();

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
            $currentUser = $request->user();
            $currentTenant = Tenant::current();
            $isSuperadmin = $currentUser->role === 'superadmin';
            
            if (!$currentTenant) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Find the user
            $user = null;
            $userTenant = null;
            
            if ($isSuperadmin) {
                // SUPERADMIN: Search across all tenants
                $tenants = Tenant::all();
                foreach ($tenants as $tenant) {
                    config(['database.connections.tenant.database' => $tenant->database]);
                    DB::purge('tenant');
                    DB::connection('tenant')->reconnect();
                    $tenant->makeCurrent();
                    
                    $found = DB::connection('tenant')
                        ->table('users')
                        ->where('id', $id)
                        ->first();
                    
                    if ($found) {
                        $user = $found;
                        $userTenant = $tenant;
                        break;
                    }
                }
            } else {
                // REGULAR USER: Only search in current tenant
                config(['database.connections.tenant.database' => $currentTenant->database]);
                DB::purge('tenant');
                DB::connection('tenant')->reconnect();
                $currentTenant->makeCurrent();
                
                $user = DB::connection('tenant')
                    ->table('users')
                    ->where('id', $id)
                    ->first();
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
            if ($user->id == $currentUser->id && $user->role === 'superadmin') {
                return response()->json([
                    'message' => 'Cannot modify your own superadmin account',
                ], 403);
            }

            // Switch to the correct tenant for update
            config(['database.connections.tenant.database' => $userTenant->database]);
            DB::purge('tenant');
            DB::connection('tenant')->reconnect();
            $userTenant->makeCurrent();

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => ['sometimes', 'string', 'email', 'max:255'],
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
            $updateData['updated_at'] = now();
            
            DB::connection('tenant')->table('users')->where('id', $id)->update($updateData);
            
            $updatedUser = DB::connection('tenant')->table('users')->where('id', $id)->first();

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
            $currentUser = auth()->user();
            $currentTenant = Tenant::current();
            $isSuperadmin = $currentUser->role === 'superadmin';
            
            if (!$currentTenant) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Find the user
            $user = null;
            $userTenant = null;
            
            if ($isSuperadmin) {
                // SUPERADMIN: Search across all tenants
                $tenants = Tenant::all();
                foreach ($tenants as $tenant) {
                    config(['database.connections.tenant.database' => $tenant->database]);
                    DB::purge('tenant');
                    DB::connection('tenant')->reconnect();
                    $tenant->makeCurrent();
                    
                    $found = DB::connection('tenant')
                        ->table('users')
                        ->where('id', $id)
                        ->first();
                    
                    if ($found) {
                        $user = $found;
                        $userTenant = $tenant;
                        break;
                    }
                }
            } else {
                // REGULAR USER: Only search in current tenant
                config(['database.connections.tenant.database' => $currentTenant->database]);
                DB::purge('tenant');
                DB::connection('tenant')->reconnect();
                $currentTenant->makeCurrent();
                
                $user = DB::connection('tenant')
                    ->table('users')
                    ->where('id', $id)
                    ->first();
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
                $superadminCount = DB::connection('tenant')->table('users')->where('role', 'superadmin')->count();
                if ($superadminCount <= 1) {
                    return response()->json([
                        'message' => 'Cannot delete the last superadmin user',
                    ], 422);
                }
            }

            // Prevent deleting yourself
            if ($user->id == $currentUser->id) {
                return response()->json([
                    'message' => 'Cannot delete your own account',
                ], 422);
            }

            // Switch to the correct tenant for deletion
            config(['database.connections.tenant.database' => $userTenant->database]);
            DB::purge('tenant');
            DB::connection('tenant')->reconnect();
            $userTenant->makeCurrent();

            DB::connection('tenant')->table('users')->where('id', $id)->delete();

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
            $currentUser = auth()->user();
            $currentTenant = Tenant::current();
            $isSuperadmin = $currentUser->role === 'superadmin';
            
            if (!$currentTenant) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Find the user
            $user = null;
            $userTenant = null;
            
            if ($isSuperadmin) {
                $tenants = Tenant::all();
                foreach ($tenants as $tenant) {
                    config(['database.connections.tenant.database' => $tenant->database]);
                    DB::purge('tenant');
                    DB::connection('tenant')->reconnect();
                    $tenant->makeCurrent();
                    
                    $found = DB::connection('tenant')
                        ->table('users')
                        ->where('id', $id)
                        ->first();
                    
                    if ($found) {
                        $user = $found;
                        $userTenant = $tenant;
                        break;
                    }
                }
            } else {
                config(['database.connections.tenant.database' => $currentTenant->database]);
                DB::purge('tenant');
                DB::connection('tenant')->reconnect();
                $currentTenant->makeCurrent();
                
                $user = DB::connection('tenant')
                    ->table('users')
                    ->where('id', $id)
                    ->first();
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
            config(['database.connections.tenant.database' => $userTenant->database]);
            DB::purge('tenant');
            DB::connection('tenant')->reconnect();
            $userTenant->makeCurrent();

            DB::connection('tenant')->table('users')->where('id', $id)->update([
                'role' => $validated['role'],
                'updated_at' => now(),
            ]);
            
            $updatedUser = DB::connection('tenant')->table('users')->where('id', $id)->first();

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
        $roles = $this->getAvailableRoles($currentUser->role);
        
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