<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SetTenantContext
{
    public function handle(Request $request, Closure $next)
    {
        // First, check if tenant is already set
        if (Tenant::current()) {
            return $next($request);
        }

        // Try to get tenant from authenticated user's token
        $user = $request->user();
        if ($user) {
            // The user is authenticated, find which tenant they belong to
            $tenants = Tenant::all();
            foreach ($tenants as $tenant) {
                // Temporarily switch to check if user exists in this tenant
                config(['database.connections.tenant.database' => $tenant->database]);
                DB::purge('tenant');
                DB::connection('tenant')->reconnect();
                
                $userExists = DB::connection('tenant')
                    ->table('users')
                    ->where('id', $user->id)
                    ->exists();
                
                if ($userExists) {
                    $this->switchToTenant($tenant);
                    Log::info('Tenant context set from authenticated user: ' . $user->email . ' -> ' . $tenant->name);
                    return $next($request);
                }
            }
        }

        // If no authenticated user, try to get tenant from domain parameter
        $domain = $request->input('domain') ?? $request->route('domain');
        if ($domain) {
            $tenant = Tenant::where('domain', $domain)->first();
            if ($tenant) {
                $this->switchToTenant($tenant);
                Log::info('Tenant context set from domain: ' . $domain);
                return $next($request);
            }
        }

        // If still no tenant, try to get from the token directly
        $token = $request->bearerToken();
        if ($token) {
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
                        $tenants = Tenant::all();
                        foreach ($tenants as $tenant) {
                            // Check if user exists in this tenant
                            config(['database.connections.tenant.database' => $tenant->database]);
                            DB::purge('tenant');
                            DB::connection('tenant')->reconnect();
                            
                            $userExists = DB::connection('tenant')
                                ->table('users')
                                ->where('id', $tokenRecord->tokenable_id)
                                ->exists();
                            
                            if ($userExists) {
                                $this->switchToTenant($tenant);
                                Log::info('Tenant context set from token: ' . $tenant->name);
                                return $next($request);
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
        
        Log::info('Switched to tenant database: ' . DB::connection('tenant')->getDatabaseName());
    }
}