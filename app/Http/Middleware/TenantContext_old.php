<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TenantContext
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('=== TenantContext Middleware ===');

        // Check if tenant is already set
        if (Tenant::current()) {
            Log::info('Tenant already set: ' . Tenant::current()->name);
            // Ensure tenant guard is used
            Auth::shouldUse('tenant');
            return $next($request);
        }

        // Try to get tenant from request
        $domain = $request->input('domain') ?? $request->header('X-Tenant-Domain') ?? $request->route('domain');
        
        if ($domain) {
            $tenant = Tenant::where('domain', $domain)->first();
            if ($tenant) {
                $this->switchToTenant($tenant);
                Auth::shouldUse('tenant');
                session(['tenant_id' => $tenant->id]);
                session(['tenant_domain' => $tenant->domain]);
                return $next($request);
            }
        }

        // Try to get tenant from authenticated user's token
        $user = $request->user('landlord');
        if ($user) {
            // Check all tenants for this user
            $tenants = Tenant::all();
            foreach ($tenants as $tenant) {
                try {
                    $this->switchToTenant($tenant);
                    $exists = DB::connection('tenant')
                        ->table('users')
                        ->where('id', $user->id)
                        ->exists();
                    
                    if ($exists) {
                        Log::info('Tenant found for user: ' . $tenant->name);
                        Auth::shouldUse('tenant');
                        session(['tenant_id' => $tenant->id]);
                        session(['tenant_domain' => $tenant->domain]);
                        return $next($request);
                    }
                } catch (\Exception $e) {
                    Log::error('Error checking tenant: ' . $e->getMessage());
                }
            }
        }

        // If we reach here, no tenant context could be established
        // For tenant routes, this should return an error
        Log::warning('No tenant context could be established');
        
        return response()->json([
            'message' => 'Tenant context required',
            'error' => 'No valid tenant found'
        ], 400);
    }

    private function switchToTenant(Tenant $tenant): void
    {
        // Get credentials from landlord config
        $host = Config::get('database.connections.landlord.host', env('DB_HOST', '127.0.0.1'));
        $port = Config::get('database.connections.landlord.port', env('DB_PORT', '3306'));
        $username = Config::get('database.connections.landlord.username', env('DB_USERNAME', 'root'));
        $password = Config::get('database.connections.landlord.password', env('DB_PASSWORD', ''));

        // Configure tenant connection
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

        // Purge and reconnect
        DB::purge('tenant');
        DB::connection('tenant')->reconnect();
        
        // Set default connection
        Config::set('database.default', 'tenant');
        
        // Make tenant current
        $tenant->makeCurrent();
        
        // Force tenant guard
        Config::set('auth.defaults.guard', 'tenant');
        
        Log::info('Switched to tenant: ' . $tenant->database);
    }
}