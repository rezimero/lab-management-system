<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Models\Landlord\User as LandlordUser;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;

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
            Config::set('database.default', 'tenant');
            $tenant->makeCurrent();

            Log::info('Switched to tenant database: ' . DB::connection('tenant')->getDatabaseName());

        } catch (\Exception $e) {
            Log::error('Failed to switch to tenant: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Login user - Clean separation between Landlord and Tenant
     * 
     * FLOW:
     * 1. Check Landlord database first (Superadmin)
     * 2. If found → Login as Superadmin (no domain required)
     * 3. If not found → Check Tenant database (domain required)
     */
    public function login(Request $request)
    {
        try {
            Log::info('=== LOGIN REQUEST ===');
            Log::info('Request data:', $request->all());
            
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'domain' => 'sometimes|string', // Optional, only for tenant users
            ]);

            // ============================================================
            // STEP 1: Check Landlord Database (Superadmin)
            // ============================================================
            $landlordUser = LandlordUser::where('email', $validated['email'])->first();
            
            if ($landlordUser) {
                Log::info('Landlord user found: ' . $landlordUser->email);
                
                // Verify password
                if (!Hash::check($validated['password'], $landlordUser->password)) {
                    Log::warning('Invalid password for landlord user: ' . $validated['email']);
                    return response()->json([
                        'message' => 'Invalid credentials',
                        'errors' => ['email' => ['Invalid credentials.']]
                    ], 422);
                }

                // Check if user is active
                if (!$landlordUser->is_active) {
                    return response()->json([
                        'message' => 'This account is deactivated.',
                        'errors' => ['email' => ['This account is deactivated.']]
                    ], 422);
                }

                // Check if user is superadmin
                if ($landlordUser->role !== 'superadmin') {
                    return response()->json([
                        'message' => 'Unauthorized',
                        'errors' => ['email' => ['You are not authorized to login here.']]
                    ], 403);
                }

                // ============================================================
                // SUPERADMIN LOGIN - No tenant required
                // ============================================================
                Log::info('Superadmin login successful: ' . $landlordUser->email);

                // Clear any existing session
                session()->forget(['tenant_id', 'tenant_domain', 'tenant_user_id', 'tenant_user_email', 'tenant_user_role']);
                
                // Store superadmin flag in session
                session([
                    'is_superadmin' => true,
                    'landlord_user_id' => $landlordUser->id,
                    'landlord_user_email' => $landlordUser->email,
                ]);

                // Create token using landlord guard
                $token = $landlordUser->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'message' => 'Login successful (Superadmin)',
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
            
            // Domain is required for tenant login
            if (empty($validated['domain'])) {
                return response()->json([
                    'message' => 'Domain required for tenant login',
                    'errors' => ['domain' => ['Domain is required for tenant login.']]
                ], 422);
            }

            // Find tenant by domain
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

            // Switch to tenant
            $this->switchToTenant($tenant);

            // Find user in tenant database
            $tenantUser = TenantUser::where('email', $validated['email'])->first();

            if (!$tenantUser) {
                Log::warning('Tenant user not found: ' . $validated['email']);
                return response()->json([
                    'message' => 'Invalid credentials',
                    'errors' => ['email' => ['Invalid credentials for this lab.']]
                ], 422);
            }

            if (!Hash::check($validated['password'], $tenantUser->password)) {
                Log::warning('Invalid password for tenant user: ' . $validated['email']);
                return response()->json([
                    'message' => 'Invalid credentials',
                    'errors' => ['email' => ['Invalid credentials for this lab.']]
                ], 422);
            }

            if (!$tenantUser->is_active) {
                return response()->json([
                    'message' => 'This account is deactivated.',
                    'errors' => ['email' => ['This account is deactivated.']]
                ], 422);
            }

            // ============================================================
            // TENANT USER LOGIN
            // ============================================================
            Log::info('Tenant login successful: ' . $tenantUser->email . ' in ' . $tenant->domain);

            // Clear any existing session
            session()->forget(['is_superadmin', 'landlord_user_id', 'landlord_user_email']);
            
            // Store tenant context in session
            session([
                'tenant_id' => $tenant->id,
                'tenant_domain' => $tenant->domain,
                'tenant_user_id' => $tenantUser->id,
                'tenant_user_email' => $tenantUser->email,
                'tenant_user_role' => $tenantUser->role,
                'is_superadmin' => false,
            ]);

            // Create token using tenant guard
            $token = $tenantUser->createToken('auth_token')->plainTextToken;

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

            // Check if superadmin (from landlord guard)
            if (session('is_superadmin', false)) {
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
            // Check if superadmin
            if (session('is_superadmin', false)) {
                return response()->json([
                    'tenant' => null,
                    'is_superadmin' => true,
                    'message' => 'Superadmin has no tenant',
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

    /**
     * Register a new tenant
     */
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'password' => 'required|string|min:8|confirmed',
                'tenant_name' => 'required|string|max:255',
                'tenant_domain' => 'required|string|max:255|unique:tenants,domain',
            ]);

            // Generate database name
            $databaseName = 'tenant_' . uniqid() . '_' . strtolower(str_replace(' ', '-', $validated['tenant_domain']));
            $databaseName = preg_replace('/[^a-zA-Z0-9_-]/', '', $databaseName);

            // Create database
            DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}`");

            // Create tenant
            $tenant = Tenant::create([
                'name' => $validated['tenant_name'],
                'domain' => $validated['tenant_domain'],
                'database' => $databaseName,
                'is_active' => true,
                'trial_ends_at' => now()->addDays(30),
            ]);

            // Switch to tenant
            $this->switchToTenant($tenant);

            // Run migrations for tenant
            \Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);

            // Create admin user
            $user = TenantUser::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'admin',
                'is_active' => true,
            ]);

            // Create token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Tenant and user created successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
                'token' => $token,
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}