<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Models\Patient;
use App\Models\LabTest;
use App\Models\TestResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class DashboardController extends Controller
{
    /**
     * Get global statistics across all tenants
     */
    public function stats()
    {
        try {
            $stats = [
                'total_tenants' => Tenant::count(),
                'total_users' => 0,
                'total_patients' => 0,
                'total_lab_tests' => 0,
                'total_test_results' => 0,
                'tenants' => [],
            ];

            foreach (Tenant::all() as $tenant) {
                try {
                    $this->switchToTenant($tenant);
                    
                    $userCount = TenantUser::count();
                    $patientCount = Patient::count();
                    $labTestCount = LabTest::count();
                    $testResultCount = TestResult::count();
                    
                    $stats['total_users'] += $userCount;
                    $stats['total_patients'] += $patientCount;
                    $stats['total_lab_tests'] += $labTestCount;
                    $stats['total_test_results'] += $testResultCount;
                    
                    $stats['tenants'][] = [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'domain' => $tenant->domain,
                        'is_active' => $tenant->is_active,
                        'stats' => [
                            'users' => $userCount,
                            'patients' => $patientCount,
                            'lab_tests' => $labTestCount,
                            'test_results' => $testResultCount,
                        ],
                    ];
                } catch (\Exception $e) {
                    Log::error('Error getting stats for tenant: ' . $tenant->name . ' - ' . $e->getMessage());
                }
            }

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Error getting global stats: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to get stats'], 500);
        }
    }

    /**
     * Get overview dashboard data
     */
    public function overview()
    {
        try {
            $stats = $this->stats()->getData();
            
            $overview = [
                'tenants' => [
                    'total' => $stats->total_tenants,
                    'active' => Tenant::where('is_active', true)->count(),
                    'suspended' => Tenant::where('is_active', false)->count(),
                ],
                'users' => [
                    'total' => $stats->total_users,
                    'by_role' => $this->getUserRolesAcrossTenants(),
                ],
                'patients' => [
                    'total' => $stats->total_patients,
                ],
                'lab_tests' => [
                    'total' => $stats->total_lab_tests,
                ],
                'test_results' => [
                    'total' => $stats->total_test_results,
                ],
            ];

            return response()->json($overview);

        } catch (\Exception $e) {
            Log::error('Error getting overview: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to get overview'], 500);
        }
    }

    /**
     * Get recent activity across all tenants
     */
    public function recentActivity(Request $request)
    {
        try {
            $limit = $request->input('limit', 20);
            $activities = [];

            foreach (Tenant::all() as $tenant) {
                try {
                    $this->switchToTenant($tenant);
                    
                    $recentUsers = TenantUser::orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get()
                        ->map(function($user) use ($tenant) {
                            return [
                                'type' => 'user_created',
                                'tenant' => $tenant->name,
                                'user' => $user->name,
                                'email' => $user->email,
                                'role' => $user->role,
                                'created_at' => $user->created_at,
                            ];
                        });
                    
                    $activities = array_merge($activities, $recentUsers->toArray());
                } catch (\Exception $e) {
                    Log::error('Error getting activity for tenant: ' . $tenant->name);
                }
            }

            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            $activities = array_slice($activities, 0, $limit);

            Config::set('database.default', 'landlord');
            DB::connection('landlord')->reconnect();

            return response()->json([
                'activities' => $activities,
                'total' => count($activities),
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting recent activity: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to get activity'], 500);
        }
    }

    /**
     * Get user roles across all tenants
     */
    private function getUserRolesAcrossTenants()
    {
        $roles = [];

        foreach (Tenant::all() as $tenant) {
            try {
                $this->switchToTenant($tenant);
                
                $tenantRoles = TenantUser::select('role', DB::raw('count(*) as count'))
                    ->groupBy('role')
                    ->get();
                
                foreach ($tenantRoles as $role) {
                    if (!isset($roles[$role->role])) {
                        $roles[$role->role] = 0;
                    }
                    $roles[$role->role] += $role->count;
                }
            } catch (\Exception $e) {
                Log::error('Error getting roles for tenant: ' . $tenant->name);
            }
        }

        Config::set('database.default', 'landlord');
        DB::connection('landlord')->reconnect();

        return $roles;
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