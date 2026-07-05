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

        // Purge and reconnect
        DB::purge('tenant');
        DB::connection('tenant')->reconnect();
        
        // Set default connection to tenant
        config(['database.default' => 'tenant']);
        
        // Make tenant current
        $tenant->makeCurrent();
        
        // Store tenant in session
        session(['tenant_id' => $tenant->id]);
        session(['tenant_domain' => $tenant->domain]);
        session(['tenant_database' => $tenant->database]);
        
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
     * Login user - FIXED: Only checks the specified tenant
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

            // Find tenant by domain FIRST
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

            // Switch to the SPECIFIED tenant database
            $this->switchToTenant($tenant);

            // Find user ONLY in this specific tenant
            $user = User::where('email', $validated['email'])->first();

            // Check if user exists in this tenant AND password matches
            if (!$user || !Hash::check($validated['password'], $user->password)) {
                Log::warning('Login failed for tenant: ' . $tenant->domain . ' - Invalid credentials');
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect for this lab.'],
                ]);
            }

            // Check if user is active
            if (!$user->is_active) {
                throw ValidationException::withMessages([
                    'email' => ['This user account is deactivated.'],
                ]);
            }

            // IMPORTANT FIX: Store the user's role from the tenant database in the session
            session(['tenant_user_role' => $user->role]);
            session(['tenant_user_id' => $user->id]);
            session(['tenant_user_email' => $user->email]);

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('Login successful for user: ' . $user->email . ' in tenant: ' . $tenant->domain . ' with role: ' . $user->role);

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
                // Add user role explicitly for frontend
                'user_role' => $user->role,
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
            
            // Clear tenant session
            session()->forget('tenant_id');
            session()->forget('tenant_domain');
            session()->forget('tenant_database');
            session()->forget('tenant_user_role');
            session()->forget('tenant_user_id');
            session()->forget('tenant_user_email');
            
            return response()->json([
                'message' => 'Logged out successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Logout error:', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Logged out successfully',
            ], 200);
        }
    }

    /**
     * Get current user - FIXED: Returns user from tenant database
     */
    public function user(Request $request)
    {
        try {
            Log::info('User endpoint called');
            
            // Get the authenticated user from the request
            $authUser = $request->user();
            
            if (!$authUser) {
                Log::warning('User not authenticated');
                return response()->json([
                    'message' => 'User not authenticated',
                ], 401);
            }

            // Get the current tenant
            $tenant = Tenant::current();
            
            if (!$tenant) {
                Log::warning('Tenant not found');
                return response()->json([
                    'message' => 'Tenant not found',
                ], 404);
            }

            // IMPORTANT FIX: Get the user from the TENANT database, not the landlord database
            $this->switchToTenant($tenant);
            
            // Fetch the user from the tenant database using the ID from the authenticated user
            $user = User::where('id', $authUser->id)->first();
            
            if (!$user) {
                Log::warning('User not found in tenant database: ' . $tenant->name);
                return response()->json([
                    'message' => 'User not found in tenant',
                ], 404);
            }
            
            Log::info('User found in tenant database:', [
                'user_id' => $user->id, 
                'email' => $user->email,
                'role' => $user->role,
                'tenant' => $tenant->name
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
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
                'user_role' => $user->role, // Explicitly return the role
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