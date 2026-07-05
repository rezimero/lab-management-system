<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Impersonate a tenant (Enter any tenant as admin)
     */
    public function impersonate(Request $request, $tenantId)
    {
        try {
            $tenant = Tenant::find($tenantId);
            if (!$tenant) {
                return response()->json(['message' => 'Tenant not found'], 404);
            }

            // Check if tenant is active
            if (!$tenant->is_active) {
                return response()->json(['message' => 'Cannot impersonate a suspended tenant'], 422);
            }

            // Switch to tenant
            $this->switchToTenant($tenant);

            // Get the first admin user in this tenant
            $adminUser = TenantUser::where('role', 'admin')->first();
            if (!$adminUser) {
                // If no admin, get any user
                $adminUser = TenantUser::first();
            }

            if (!$adminUser) {
                return response()->json(['message' => 'No users found in this tenant'], 404);
            }

            // Store impersonation state
            session([
                'impersonating' => true,
                'impersonated_tenant_id' => $tenant->id,
                'impersonated_tenant_name' => $tenant->name,
                'impersonated_user_id' => $adminUser->id,
                'impersonated_user_email' => $adminUser->email,
            ]);

            // Switch back to landlord
            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            // Create a token for the impersonated user
            $token = $adminUser->createToken('impersonation_token')->plainTextToken;

            return response()->json([
                'message' => 'Impersonating tenant: ' . $tenant->name,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
                'user' => [
                    'id' => $adminUser->id,
                    'name' => $adminUser->name,
                    'email' => $adminUser->email,
                    'role' => $adminUser->role,
                ],
                'token' => $token,
                'is_impersonating' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Error impersonating tenant: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to impersonate tenant'], 500);
        }
    }

    /**
     * Stop impersonating
     */
    public function stopImpersonating(Request $request)
    {
        try {
            session()->forget([
                'impersonating',
                'impersonated_tenant_id',
                'impersonated_tenant_name',
                'impersonated_user_id',
                'impersonated_user_email',
            ]);

            return response()->json([
                'message' => 'Stopped impersonating',
            ]);

        } catch (\Exception $e) {
            Log::error('Error stopping impersonation: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to stop impersonation'], 500);
        }
    }

    /**
     * Get impersonation status
     */
    public function status()
    {
        return response()->json([
            'is_impersonating' => session('impersonating', false),
            'tenant' => session('impersonating') ? [
                'id' => session('impersonated_tenant_id'),
                'name' => session('impersonated_tenant_name'),
            ] : null,
            'user' => session('impersonating') ? [
                'id' => session('impersonated_user_id'),
                'email' => session('impersonated_user_email'),
            ] : null,
        ]);
    }

    /**
     * Switch to tenant database
     */
    private function switchToTenant(Tenant $tenant): void
    {
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
    }
}