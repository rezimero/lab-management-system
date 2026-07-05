<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\LabTest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class LabTestController extends Controller
{
    /**
     * List all lab tests across all tenants (God Mode)
     */
    public function index(Request $request)
    {
        try {
            $allLabTests = [];
            $tenants = Tenant::all();

            foreach ($tenants as $tenant) {
                try {
                    $this->switchToTenant($tenant);
                    
                    $labTests = LabTest::all();
                    
                    foreach ($labTests as $labTest) {
                        $labTestArray = $labTest->toArray();
                        $labTestArray['tenant_name'] = $tenant->name;
                        $labTestArray['tenant_domain'] = $tenant->domain;
                        $allLabTests[] = $labTestArray;
                    }
                } catch (\Exception $e) {
                    Log::error('Error fetching lab tests from tenant: ' . $tenant->name);
                }
            }

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $allLabTests = array_filter($allLabTests, function($labTest) use ($search) {
                    return stripos($labTest['name'], $search) !== false || 
                           stripos($labTest['category'], $search) !== false;
                });
            }

            usort($allLabTests, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
            $total = count($allLabTests);
            $offset = ($page - 1) * $perPage;
            $labTestData = array_slice($allLabTests, $offset, $perPage);

            return response()->json([
                'lab_tests' => [
                    'data' => $labTestData,
                    'total' => $total,
                    'per_page' => (int)$perPage,
                    'current_page' => (int)$page,
                    'last_page' => ceil($total / $perPage),
                ],
                'total_tenants' => $tenants->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching all lab tests: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch lab tests'], 500);
        }
    }

    /**
     * Get a single lab test from any tenant (God Mode)
     */
    public function show($id)
    {
        try {
            $tenants = Tenant::all();
            
            foreach ($tenants as $tenant) {
                try {
                    $this->switchToTenant($tenant);
                    
                    $labTest = LabTest::where('id', $id)->first();
                    if ($labTest) {
                        $labTestArray = $labTest->toArray();
                        $labTestArray['tenant_name'] = $tenant->name;
                        $labTestArray['tenant_domain'] = $tenant->domain;
                        
                        Config::set('database.default', 'landlord');
                        DB::connection('landlord')->reconnect();
                        
                        return response()->json($labTestArray);
                    }
                } catch (\Exception $e) {
                    Log::error('Error searching tenant: ' . $tenant->name);
                }
            }

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json(['message' => 'Lab test not found'], 404);

        } catch (\Exception $e) {
            Log::error('Error finding lab test: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to find lab test'], 500);
        }
    }

    /**
     * Delete a lab test from any tenant (God Mode)
     */
    public function destroy($id)
    {
        try {
            $tenant = null;
            $labTest = null;

            $tenants = Tenant::all();
            foreach ($tenants as $t) {
                try {
                    $this->switchToTenant($t);
                    
                    $found = LabTest::where('id', $id)->first();
                    if ($found) {
                        $labTest = $found;
                        $tenant = $t;
                        break;
                    }
                } catch (\Exception $e) {
                    Log::error('Error searching tenant: ' . $t->name);
                }
            }

            if (!$labTest) {
                Config::set('database.default', 'landlord');
                DB::connection('landlord')->reconnect();
                return response()->json(['message' => 'Lab test not found'], 404);
            }

            $labTest->delete();

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json([
                'message' => 'Lab test deleted successfully',
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting lab test: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete lab test'], 500);
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
        Config::set('database.default', 'tenant');
        $tenant->makeCurrent();
    }
}