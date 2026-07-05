<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class UserController extends Controller
{
    /**
     * List ALL users from ALL tenants (God Mode)
     */
    public function index(Request $request)
    {
        try {
            $allUsers = [];
            $tenants = Tenant::all();

            Log::info('Landlord fetching users from ' . $tenants->count() . ' tenants');

            foreach ($tenants as $tenant) {
                try {
                    $this->switchToTenant($tenant);

                    $users = TenantUser::all();

                    Log::info('Tenant: ' . $tenant->name . ' - Users: ' . $users->count());

                    foreach ($users as $user) {
                        $userArray = $user->toArray();
                        $userArray['tenant_name'] = $tenant->name;
                        $userArray['tenant_domain'] = $tenant->domain;
                        $allUsers[] = $userArray;
                    }
                } catch (\Exception $e) {
                    Log::error('Error fetching users from tenant: ' . $tenant->name . ' - ' . $e->getMessage());
                }
            }

            // Switch back to landlord
            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            Log::info('Total users found: ' . count($allUsers));

            return response()->json([
                'users' => [
                    'data' => $allUsers,
                    'total' => count($allUsers),
                    'per_page' => 15,
                    'current_page' => 1,
                    'last_page' => 1,
                ],
                'total_tenants' => $tenants->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error in Landlord UserController: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch users: ' . $e->getMessage()], 500);
        }
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
        $tenant->makeCurrent();
    }
}