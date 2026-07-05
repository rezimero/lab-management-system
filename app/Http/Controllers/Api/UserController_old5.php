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

class UserController extends Controller
{
    /**
     * List all users - ONLY CURRENT TENANT
     * Uses session to get the current user
     */
    public function index(Request $request)
    {
        try {
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
            
            // If session doesn't have user, try to get from request
            if (!$userEmail) {
                $authUser = $request->user();
                if ($authUser) {
                    $userEmail = $authUser->email;
                    // Try to find in tenant database
                    $user = TenantUser::where('email', $userEmail)->first();
                    if ($user) {
                        $userId = $user->id;
                        $userRole = $user->role;
                        // Store in session for next time
                        session(['tenant_user_id' => $userId]);
                        session(['tenant_user_email' => $userEmail]);
                        session(['tenant_user_role' => $userRole]);
                    }
                }
            }
            
            // If still no user, try to get any user from tenant
            if (!$userEmail) {
                $anyUser = TenantUser::first();
                if ($anyUser) {
                    $userEmail = $anyUser->email;
                    $userId = $anyUser->id;
                    $userRole = $anyUser->role;
                    Log::warning('Using fallback user: ' . $userEmail);
                } else {
                    return response()->json([
                        'users' => ['data' => [], 'total' => 0],
                        'current_user_role' => 'guest',
                        'is_superadmin' => false,
                        'tenant_name' => $tenant->name,
                    ]);
                }
            }

            // If we have email but no role, get from database
            if (!$userRole) {
                $user = TenantUser::where('email', $userEmail)->first();
                if ($user) {
                    $userRole = $user->role;
                    $userId = $user->id;
                } else {
                    $userRole = 'user';
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
     */
    public function show($id)
    {
        try {
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
     */
    public function store(Request $request)
    {
        try {
            $tenant = Tenant::current();
            if (!$tenant) {
                return response()->json(['message' => 'No tenant context'], 400);
            }

            Config::set('database.default', 'tenant');
            DB::connection('tenant')->reconnect();

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|string|in:superadmin,admin,lab_technician,receptionist,doctor',
                'is_active' => 'sometimes|boolean',
            ]);

            $user = TenantUser::create([
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

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'password' => 'sometimes|string|min:8',
                'role' => 'sometimes|string|in:superadmin,admin,lab_technician,receptionist,doctor',
                'is_active' => 'sometimes|boolean',
            ]);

            $updateData = [];
            if (isset($validated['name'])) $updateData['name'] = $validated['name'];
            if (isset($validated['email'])) $updateData['email'] = $validated['email'];
            if (isset($validated['role'])) $updateData['role'] = $validated['role'];
            if (isset($validated['is_active'])) $updateData['is_active'] = $validated['is_active'];
            if (isset($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }
            
            $user->update($updateData);

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
     */
    public function destroy($id)
    {
        try {
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

            $user->delete();

            return response()->json(['message' => 'User deleted successfully']);
            
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

            $validated = $request->validate([
                'role' => 'required|string|in:superadmin,admin,lab_technician,receptionist,doctor',
            ]);

            $user->update(['role' => $validated['role']]);

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
        $allRoles = [
            'superadmin' => 'Super Administrator - Full system access',
            'admin' => 'Administrator - Manage all lab operations',
            'lab_technician' => 'Lab Technician - Perform and enter test results',
            'receptionist' => 'Receptionist - Register patients and manage appointments',
            'doctor' => 'Doctor - View patient data and approve results',
        ];
        
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