<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    protected $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Switch to tenant database connection
     */
    private function switchToTenant(Tenant $tenant): void
    {
        // Force the database connection
        config([
            'database.connections.tenant.database' => $tenant->database,
            'database.connections.tenant.host' => env('DB_HOST', '127.0.0.1'),
            'database.connections.tenant.port' => env('DB_PORT', '3306'),
            'database.connections.tenant.username' => env('DB_USERNAME', 'root'),
            'database.connections.tenant.password' => env('DB_PASSWORD', ''),
        ]);

        // Purge and reconnect
        DB::purge('tenant');
        DB::connection('tenant')->reconnect();
        
        // Make tenant current
        $tenant->makeCurrent();
        
        // Store tenant in session for middleware
        session(['tenant_id' => $tenant->id]);
        session(['domain' => $tenant->domain]);
        
        Log::info('Switched to tenant database: ' . DB::connection('tenant')->getDatabaseName());
    }

    /**
     * Register a new tenant with admin user
     */
    public function register(Request $request)
    {
        try {
            Log::info('=== REGISTER REQUEST START ===');
            Log::info('Request data:', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'tenant_name' => 'required|string|max:255',
                'tenant_domain' => 'required|string|max:255|unique:tenants,domain',
            ]);

            Log::info('Validation passed');

            // Create tenant and admin user
            $tenant = $this->tenantService->createTenant([
                'name' => $validated['tenant_name'],
                'domain' => $validated['tenant_domain'],
                'admin_name' => $validated['name'],
                'admin_email' => $validated['email'],
                'admin_password' => $validated['password'],
            ]);

            Log::info('Tenant created: ' . $tenant->id);

            // Switch to tenant using the robust method
            $this->switchToTenant($tenant);
            
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                Log::error('User not found after creation');
                throw new \Exception('User not found after creation');
            }

            Log::info('User found: ' . $user->id);

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Token created successfully');

            return response()->json([
                'message' => 'Tenant and user created successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role, // This will be 'admin' from TenantService
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                    'database' => $tenant->database,
                    'is_active' => $tenant->is_active,
                    'trial_ends_at' => $tenant->trial_ends_at,
                    'created_at' => $tenant->created_at,
                    'updated_at' => $tenant->updated_at,
                ],
                'token' => $token,
            ], 201);

        } catch (ValidationException $e) {
            Log::error('Validation failed:', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Registration failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        try {
            Log::info('=== LOGIN REQUEST ===');
            Log::info('All data:', $request->all());
            
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'domain' => 'required|string',
            ]);

            // Find tenant by domain
            $tenant = Tenant::where('domain', $validated['domain'])->first();

            if (!$tenant) {
                throw ValidationException::withMessages([
                    'domain' => ['Invalid domain. Please check your lab domain.'],
                ]);
            }

            // Check if tenant is active
            if (!$tenant->is_active) {
                throw ValidationException::withMessages([
                    'domain' => ['This lab account is deactivated.'],
                ]);
            }

            // Switch to tenant using the robust method
            $this->switchToTenant($tenant);

            // Find user
            $user = User::where('email', $validated['email'])->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                    'database' => $tenant->database,
                    'is_active' => $tenant->is_active,
                    'trial_ends_at' => $tenant->trial_ends_at,
                    'created_at' => $tenant->created_at,
                    'updated_at' => $tenant->updated_at,
                ],
                'token' => $token,
            ]);

        } catch (ValidationException $e) {
            Log::error('Validation failed:', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            // Check if user is authenticated
            if ($request->user()) {
                // Delete the current access token
                $request->user()->currentAccessToken()->delete();
                Log::info('User logged out successfully: ' . $request->user()->email);
            }
            
            return response()->json([
                'message' => 'Logged out successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Logout error:', ['error' => $e->getMessage()]);
            // Return success even if there's an error, to avoid frontend issues
            return response()->json([
                'message' => 'Logged out successfully',
            ], 200);
        }
    }

    /**
     * Get current user
     */
    public function user(Request $request)
    {
        try {
            Log::info('User endpoint called');
            
            // Get the authenticated user
            $user = $request->user();
            
            if (!$user) {
                Log::warning('User not authenticated');
                return response()->json([
                    'message' => 'User not authenticated',
                ], 401);
            }

            // Get the current tenant
            $tenant = Tenant::current();
            
            Log::info('User found:', [
                'user_id' => $user->id, 
                'email' => $user->email,
                'tenant' => $tenant ? $tenant->id : 'none'
            ]);

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'tenant' => $tenant ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ] : null,
            ]);
        } catch (\Exception $e) {
            Log::error('User endpoint error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Failed to get user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get current tenant
     */
    public function tenant(Request $request)
    {
        try {
            Log::info('Tenant endpoint called');
            
            $tenant = Tenant::current();

            if (!$tenant) {
                Log::warning('Tenant not found');
                return response()->json([
                    'message' => 'Tenant not found',
                ], 404);
            }

            Log::info('Tenant found:', ['tenant_id' => $tenant->id, 'name' => $tenant->name]);

            return response()->json([
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                    'database' => $tenant->database,
                    'is_active' => $tenant->is_active,
                    'trial_ends_at' => $tenant->trial_ends_at,
                    'created_at' => $tenant->created_at,
                    'updated_at' => $tenant->updated_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Tenant endpoint error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Failed to get tenant',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}