<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\User as TenantUser;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * List users in the current tenant ONLY
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

            $currentUser = Auth::guard('tenant')->user();

            if (!$currentUser) {
                return response()->json([
                    'users' => ['data' => [], 'total' => 0],
                    'current_user_role' => 'guest',
                    'is_superadmin' => false,
                    'tenant_name' => $tenant->name,
                ]);
            }

            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);

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

            return response()->json([
                'users' => [
                    'data' => $users,
                    'total' => $total,
                    'per_page' => (int)$perPage,
                    'current_page' => (int)$page,
                    'last_page' => ceil($total / $perPage),
                ],
                'current_user_role' => $currentUser->role,
                'is_superadmin' => false,
                'tenant_name' => $tenant->name,
            ]);

        } catch (\Exception $e) {
            Log::error('Error in Tenant UserController: ' . $e->getMessage());
            return response()->json([
                'users' => ['data' => [], 'total' => 0],
                'error' => $e->getMessage(),
            ], 500);
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

            $currentUser = Auth::guard('tenant')->user();
            if (!$currentUser) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|string|in:admin,lab_technician,receptionist,doctor',
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
            Log::error('Error creating user: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create user'], 500);
        }
    }

    // ... other methods (show, update, delete, assignRole)
}