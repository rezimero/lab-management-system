<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class TestResultController extends Controller
{
    /**
     * List all test results across all tenants (God Mode)
     */
    public function index(Request $request)
    {
        try {
            $allTestResults = [];
            $tenants = Tenant::all();

            foreach ($tenants as $tenant) {
                try {
                    $this->switchToTenant($tenant);
                    
                    $testResults = TestResult::all();
                    
                    foreach ($testResults as $testResult) {
                        $testResultArray = $testResult->toArray();
                        $testResultArray['tenant_name'] = $tenant->name;
                        $testResultArray['tenant_domain'] = $tenant->domain;
                        $allTestResults[] = $testResultArray;
                    }
                } catch (\Exception $e) {
                    Log::error('Error fetching test results from tenant: ' . $tenant->name);
                }
            }

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $allTestResults = array_filter($allTestResults, function($testResult) use ($search) {
                    return stripos($testResult['status'], $search) !== false;
                });
            }

            usort($allTestResults, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            $perPage = $request->input('per_page', 15);
            $page = $request->input('page', 1);
            $total = count($allTestResults);
            $offset = ($page - 1) * $perPage;
            $testResultData = array_slice($allTestResults, $offset, $perPage);

            return response()->json([
                'test_results' => [
                    'data' => $testResultData,
                    'total' => $total,
                    'per_page' => (int)$perPage,
                    'current_page' => (int)$page,
                    'last_page' => ceil($total / $perPage),
                ],
                'total_tenants' => $tenants->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching all test results: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to fetch test results'], 500);
        }
    }

    /**
     * Get a single test result from any tenant (God Mode)
     */
    public function show($id)
    {
        try {
            $tenants = Tenant::all();
            
            foreach ($tenants as $tenant) {
                try {
                    $this->switchToTenant($tenant);
                    
                    $testResult = TestResult::where('id', $id)->first();
                    if ($testResult) {
                        $testResultArray = $testResult->toArray();
                        $testResultArray['tenant_name'] = $tenant->name;
                        $testResultArray['tenant_domain'] = $tenant->domain;
                        
                        Config::set('database.default', 'landlord');
                        DB::connection('landlord')->reconnect();
                        
                        return response()->json($testResultArray);
                    }
                } catch (\Exception $e) {
                    Log::error('Error searching tenant: ' . $tenant->name);
                }
            }

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json(['message' => 'Test result not found'], 404);

        } catch (\Exception $e) {
            Log::error('Error finding test result: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to find test result'], 500);
        }
    }

    /**
     * Delete a test result from any tenant (God Mode)
     */
    public function destroy($id)
    {
        try {
            $tenant = null;
            $testResult = null;

            $tenants = Tenant::all();
            foreach ($tenants as $t) {
                try {
                    $this->switchToTenant($t);
                    
                    $found = TestResult::where('id', $id)->first();
                    if ($found) {
                        $testResult = $found;
                        $tenant = $t;
                        break;
                    }
                } catch (\Exception $e) {
                    Log::error('Error searching tenant: ' . $t->name);
                }
            }

            if (!$testResult) {
                Config::set('database.default', 'landlord');
                DB::connection('landlord')->reconnect();
                return response()->json(['message' => 'Test result not found'], 404);
            }

            $testResult->delete();

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json([
                'message' => 'Test result deleted successfully',
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting test result: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete test result'], 500);
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