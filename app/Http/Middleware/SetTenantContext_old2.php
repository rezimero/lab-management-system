<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class SetTenantContext
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('=== SetTenantContext START ===');
        
        // First, check if tenant is already set
        if (Tenant::current()) {
            Log::info('Tenant already set: ' . Tenant::current()->name);
            return $next($request);
        }

        // Try to get tenant from authenticated user
        $user = $request->user();
        if ($user) {
            Log::info('User authenticated: ' . $user->email . ' (ID: ' . $user->id . ')');
            
            // Check if we have tenant in session
            $tenantId = session('tenant_id');
            if ($tenantId) {
                $tenant = Tenant::find($tenantId);
                if ($tenant) {
                    $this->switchToTenant($tenant);
                    Log::info('Tenant restored from session: ' . $tenant->name);
                    return $next($request);
                }
            }
            
            // Find which tenant this user belongs to
            $tenants = Tenant::all();
            Log::info('Checking ' . $tenants->count() . ' tenants for user');
            
            foreach ($tenants as $tenant) {
                try {
                    // Check if user exists in this tenant's database
                    $this->switchToTenant($tenant);
                    
                    $userExists = DB::connection('tenant')
                        ->table('users')
                        ->where('id', $user->id)
                        ->exists();
                    
                    if ($userExists) {
                        Log::info('Tenant context set from authenticated user: ' . $user->email . ' -> ' . $tenant->name . ' (Database: ' . $tenant->database . ')');
                        session(['tenant_id' => $tenant->id]);
                        return $next($request);
                    }
                } catch (\Exception $e) {
                    Log::error('Error checking tenant ' . $tenant->name . ': ' . $e->getMessage());
                }
            }
            
            Log::warning('No tenant found for user: ' . $user->email);
        }

        // If no authenticated user, try to get tenant from domain parameter
        $domain = $request->input('domain') ?? $request->route('domain');
        if ($domain) {
            Log::info('Looking for tenant by domain: ' . $domain);
            $tenant = Tenant::where('domain', $domain)->first();
            if ($tenant) {
                $this->switchToTenant($tenant);
                Log::info('Tenant context set from domain: ' . $domain);
                session(['tenant_id' => $tenant->id]);
                return $next($request);
            }
        }

        // If still no tenant, try to get from the token directly
        $token = $request->bearerToken();
        if ($token) {
            Log::info('No user or domain, trying to get tenant from token');
            try {
                $tokenParts = explode('|', $token);
                $tokenId = $tokenParts[0] ?? null;
                
                if ($tokenId) {
                    // Find the token in landlord database
                    $tokenRecord = DB::connection('landlord')
                        ->table('personal_access_tokens')
                        ->where('id', $tokenId)
                        ->first();
                    
                    if ($tokenRecord) {
                        Log::info('Token found in landlord database for user ID: ' . $tokenRecord->tokenable_id);
                        $tenants = Tenant::all();
                        foreach ($tenants as $tenant) {
                            try {
                                $this->switchToTenant($tenant);
                                
                                $userExists = DB::connection('tenant')
                                    ->table('users')
                                    ->where('id', $tokenRecord->tokenable_id)
                                    ->exists();
                                
                                if ($userExists) {
                                    Log::info('Tenant context set from token: ' . $tenant->name);
                                    session(['tenant_id' => $tenant->id]);
                                    return $next($request);
                                }
                            } catch (\Exception $e) {
                                Log::error('Error checking tenant from token: ' . $e->getMessage());
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error setting tenant from token:', ['error' => $e->getMessage()]);
            }
        }

        // Log warning if no tenant context could be set
        Log::warning('No tenant context could be set for request: ' . $request->fullUrl());
        
        return $next($request);
    }

    private function switchToTenant(Tenant $tenant): void
    {
        // Get database credentials from config (which reads from .env)
        $host = Config::get('database.connections.landlord.host', '127.0.0.1');
        $port = Config::get('database.connections.landlord.port', '3306');
        $username = Config::get('database.connections.landlord.username', 'root');
        $password = Config::get('database.connections.landlord.password', '');
        
        // Log the connection details (without password for security)
        Log::info('Connecting to tenant database:', [
            'database' => $tenant->database,
            'host' => $host,
            'username' => $username,
            'password_set' => !empty($password),
        ]);
        
        // Set the database name for the tenant connection
        Config::set('database.connections.tenant.host', $host);
        Config::set('database.connections.tenant.port', $port);
        Config::set('database.connections.tenant.database', $tenant->database);
        Config::set('database.connections.tenant.username', $username);
        Config::set('database.connections.tenant.password', $password);
        Config::set('database.connections.tenant.driver', 'mysql');
        Config::set('database.connections.tenant.charset', 'utf8mb4');
        Config::set('database.connections.tenant.collation', 'utf8mb4_unicode_ci');
        Config::set('database.connections.tenant.prefix', '');
        Config::set('database.connections.tenant.strict', true);
        Config::set('database.connections.tenant.engine', null);

        // Purge and reconnect to force the new connection
        DB::purge('tenant');
        
        // Reconnect with the new config
        DB::connection('tenant')->reconnect();
        
        // Also set the default connection to tenant for any model without explicit connection
        Config::set('database.default', 'tenant');
        
        // Make tenant current in the spatie package
        $tenant->makeCurrent();
        
        // Store in session
        session(['tenant_id' => $tenant->id]);
        session(['tenant_database' => $tenant->database]);
        
        // Verify the connection
        try {
            $dbName = DB::connection('tenant')->getDatabaseName();
            Log::info('✅ Switched to tenant database: ' . $dbName . ' (Tenant: ' . $tenant->name . ')');
        } catch (\Exception $e) {
            Log::error('❌ Failed to switch to tenant database: ' . $e->getMessage());
            throw $e;
        }
    }
}