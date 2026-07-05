<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Landlord\User as LandlordUser;
use App\Models\Tenant\User as TenantUser;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

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
        try {
            $host = Config::get('database.connections.landlord.host', env('DB_HOST', '127.0.0.1'));
            $port = Config::get('database.connections.landlord.port', env('DB_PORT', '3306'));
            $username = Config::get('database.connections.landlord.username', env('DB_USERNAME', 'root'));
            $password = Config::get('database.connections.landlord.password', env('DB_PASSWORD', ''));

            Log::info('Switching to tenant: ' . $tenant->database);

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
            $tenant->makeCurrent();

            Log::info('Switched to tenant database: ' . DB::connection('tenant')->getDatabaseName());

        } catch (\Exception $e) {
            Log::error('Failed to switch to tenant: ' . $e->getMessage());
            throw $e;
        }
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
                'email' => 'required|string|email|max:255',
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

            $user = TenantUser::where('email', $validated['email'])->first();

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
     * Login - Clean landlord-first authentication
     * 
     * FLOW:
     * 1. Check Landlord database (Superadmin)
     * 2. If found → Login as Superadmin (no domain required)
     * 3. If not found → Check Tenant database (domain required)
     */
    public function login(Request $request)
    {
        try {
            Log::info('=== LOGIN REQUEST ===');
            Log::info('Email:', [$request->email]);
            Log::info('Domain:', [$request->domain]);

            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'domain' => 'sometimes|string',
            ]);

            // ============================================================
            // STEP 1: Check Landlord Database (Superadmin)
            // ============================================================
            $landlordUser = LandlordUser::where('email', $validated['email'])->first();

            if ($landlordUser && Hash::check($validated['password'], $landlordUser->password)) {
                Log::info('✅ Superadmin login successful: ' . $landlordUser->email);

                // Clear any existing session
                session()->forget(['tenant_id', 'tenant_domain', 'tenant_user_id', 'tenant_user_email', 'tenant_user_role']);

                // Create token
                $token = $landlordUser->createToken('landlord_token', ['superadmin'])->plainTextToken;

                return response()->json([
                    'message' => 'Superadmin login successful',
                    'user' => [
                        'id' => $landlordUser->id,
                        'name' => $landlordUser->name,
                        'email' => $landlordUser->email,
                        'role' => 'superadmin',
                        'is_active' => $landlordUser->is_active,
                    ],
                    'token' => $token,
                    'is_superadmin' => true,
                    'guard' => 'landlord',
                ]);
            }

            // ============================================================
            // STEP 2: Check Tenant Database
            // ============================================================
            if (empty($validated['domain'])) {
                return response()->json([
                    'message' => 'Domain required for tenant login',
                    'errors' => ['domain' => ['Domain is required for tenant login.']]
                ], 422);
            }

            $tenant = Tenant::where('domain', $validated['domain'])->first();

            if (!$tenant) {
                return response()->json([
                    'message' => 'Invalid domain',
                    'errors' => ['domain' => ['Invalid domain.']]
                ], 422);
            }

            if (!$tenant->is_active) {
                return response()->json([
                    'message' => 'This lab account is deactivated.',
                    'errors' => ['domain' => ['This lab account is deactivated.']]
                ], 422);
            }

            // Switch to tenant database
            $this->switchToTenant($tenant);

            $tenantUser = TenantUser::where('email', $validated['email'])->first();

            if (!$tenantUser || !Hash::check($validated['password'], $tenantUser->password)) {
                return response()->json([
                    'message' => 'Invalid credentials for this lab.',
                    'errors' => ['email' => ['Invalid credentials for this lab.']]
                ], 422);
            }

            if (!$tenantUser->is_active) {
                return response()->json([
                    'message' => 'This account is deactivated.',
                    'errors' => ['email' => ['This account is deactivated.']]
                ], 422);
            }

            Log::info('✅ Tenant login successful: ' . $tenantUser->email . ' in ' . $tenant->domain);

            // Clear any existing session
            session()->forget(['is_superadmin', 'landlord_user_id', 'landlord_user_email']);

            // Store tenant context in session
            session([
                'tenant_id' => $tenant->id,
                'tenant_domain' => $tenant->domain,
                'tenant_user_id' => $tenantUser->id,
                'tenant_user_email' => $tenantUser->email,
                'tenant_user_role' => $tenantUser->role,
            ]);

            // Create token
            $token = $tenantUser->createToken('tenant_token', ['tenant'])->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $tenantUser->id,
                    'name' => $tenantUser->name,
                    'email' => $tenantUser->email,
                    'role' => $tenantUser->role,
                    'is_active' => $tenantUser->is_active,
                ],
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
                'token' => $token,
                'is_superadmin' => false,
                'guard' => 'tenant',
            ]);

        } catch (ValidationException $e) {
            Log::error('Validation failed:', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'Login failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            if ($user) {
                $user->currentAccessToken()->delete();
                Log::info('User logged out: ' . $user->email);
            }

            session()->forget([
                'tenant_id',
                'tenant_domain',
                'tenant_user_id',
                'tenant_user_email',
                'tenant_user_role',
                'is_superadmin',
                'landlord_user_id',
                'landlord_user_email'
            ]);

            return response()->json([
                'message' => 'Logged out successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());
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
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated',
                ], 401);
            }

            // Check if landlord user (superadmin)
            if ($user instanceof \App\Models\Landlord\User) {
                return response()->json([
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => 'superadmin',
                        'is_active' => $user->is_active,
                    ],
                    'is_superadmin' => true,
                    'guard' => 'landlord',
                    'tenant' => null,
                ]);
            }

            // Tenant user
            $tenant = Tenant::current();

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                ],
                'tenant' => $tenant ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ] : null,
                'is_superadmin' => false,
                'guard' => 'tenant',
            ]);

        } catch (\Exception $e) {
            Log::error('User endpoint error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to get user',
            ], 500);
        }
    }

    /**
     * Get current tenant
     */
    public function tenant(Request $request)
    {
        try {
            $user = $request->user();

            // If landlord user, no tenant
            if ($user instanceof \App\Models\Landlord\User) {
                return response()->json([
                    'tenant' => null,
                    'is_superadmin' => true,
                ]);
            }

            $tenant = Tenant::current();

            if (!$tenant) {
                return response()->json([
                    'message' => 'Tenant not found',
                ], 404);
            }

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
                'is_superadmin' => false,
            ]);

        } catch (\Exception $e) {
            Log::error('Tenant endpoint error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to get tenant',
            ], 500);
        }
    }
}