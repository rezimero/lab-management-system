<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class SetTenantContext
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('=== SetTenantContext Middleware ===');

        // Check if user is superadmin
        $user = $request->user();
        if ($user && $user->role === 'superadmin') {
            Log::info('Superadmin detected - skipping tenant context');
            // Superadmin doesn't need tenant context
            return $next($request);
        }

        // Check if superadmin flag is in session
        if (session('is_superadmin', false)) {
            Log::info('Superadmin session flag detected - skipping tenant context');
            return $next($request);
        }

        // Check if we have tenant in session (from login)
        $tenantId = session('tenant_id');
        $tenantDomain = session('tenant_domain');
        
        if ($tenantId) {
            $tenant = Tenant::find($tenantId);
            if ($tenant) {
                Log::info('Tenant found in session: ' . $tenant->name);
                $this->switchToTenant($tenant);
                return $next($request);
            }
        }
        
        if ($tenantDomain) {
            $tenant = Tenant::where('domain', $tenantDomain)->first();
            if ($tenant) {
                Log::info('Tenant found by domain in session: ' . $tenant->name);
                $this->switchToTenant($tenant);
                return $next($request);
            }
        }

        // Try to get tenant from request
        $domain = $request->input('domain') ?? $request->header('X-Tenant-Domain') ?? $request->route('domain');
        
        if ($domain) {
            $tenant = Tenant::where('domain', $domain)->first();
            if ($tenant) {
                Log::info('Tenant found in request: ' . $tenant->name);
                $this->switchToTenant($tenant);
                session(['tenant_id' => $tenant->id]);
                session(['tenant_domain' => $tenant->domain]);
                return $next($request);
            }
        }

        // Try to get tenant from user
        if ($user) {
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
                        session(['tenant_id' => $tenant->id]);
                        session(['tenant_domain' => $tenant->domain]);
                        return $next($request);
                    }
                } catch (\Exception $e) {
                    Log::error('Error checking tenant: ' . $e->getMessage());
                }
            }
        }

        Log::warning('No tenant context could be established');
        return $next($request);
    }

    private function switchToTenant(Tenant $tenant): void
    {
        try {
            $host = Config::get('database.connections.landlord.host', env('DB_HOST', '127.0.0.1'));
            $port = Config::get('database.connections.landlord.port', env('DB_PORT', '3306'));
            $username = Config::get('database.connections.landlord.username', env('DB_USERNAME', 'root'));
            $password = Config::get('database.connections.landlord.password', env('DB_PASSWORD', ''));

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

            Log::info('Switched to tenant: ' . $tenant->database);

        } catch (\Exception $e) {
            Log::error('Failed to switch to tenant: ' . $e->getMessage());
            throw $e;
        }
    }
}