<?php

namespace App\Services\Landlord;

use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Models\TestResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TenantStatisticsAggregator
{
    public function __construct(
        protected TenantContextRunner $tenantContextRunner,
    ) {
    }

    /**
     * Aggregate platform statistics across every tenant.
     *
     * @return array{
     *     total_tenants: int,
     *     total_users: int,
     *     total_patients: int,
     *     total_lab_tests: int,
     *     total_test_results: int,
     *     tenants: array<int, array<string, mixed>>
     * }
     */
    public function statistics(): array
    {
        $statistics = [
            'total_tenants' => Tenant::query()->count(),
            'total_users' => 0,
            'total_patients' => 0,
            'total_lab_tests' => 0,
            'total_test_results' => 0,
            'tenants' => [],
        ];

        foreach (Tenant::query()->get() as $tenant) {
            try {
                $tenantStatistics = $this->tenantContextRunner->run(
                    $tenant,
                    fn (): array => $this->collectTenantStatistics()
                );

                $statistics['total_users'] += (
                    $tenantStatistics['users']
                );

                $statistics['total_patients'] += (
                    $tenantStatistics['patients']
                );

                $statistics['total_lab_tests'] += (
                    $tenantStatistics['lab_tests']
                );

                $statistics['total_test_results'] += (
                    $tenantStatistics['test_results']
                );

                $statistics['tenants'][] = [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                    'is_active' => (bool) $tenant->is_active,
                    'stats' => $tenantStatistics,
                ];
            } catch (Throwable $exception) {
                Log::error(
                    'Failed to collect tenant dashboard statistics.',
                    [
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name,
                        'tenant_domain' => $tenant->domain,
                        'exception' => $exception->getMessage(),
                    ]
                );
            }
        }

        return $statistics;
    }

    /**
     * Aggregate user counts grouped by role across every tenant.
     *
     * @return array<string, int>
     */
    public function userRoles(): array
    {
        $roles = [];

        foreach (Tenant::query()->get() as $tenant) {
            try {
                $tenantRoles = $this->tenantContextRunner->run(
                    $tenant,
                    fn () => TenantUser::query()
                        ->select(
                            'role',
                            DB::raw('count(*) as count')
                        )
                        ->groupBy('role')
                        ->get()
                );

                foreach ($tenantRoles as $tenantRole) {
                    $role = (string) $tenantRole->role;

                    if (! array_key_exists($role, $roles)) {
                        $roles[$role] = 0;
                    }

                    $roles[$role] += (int) $tenantRole->count;
                }
            } catch (Throwable $exception) {
                Log::error(
                    'Failed to collect tenant user-role statistics.',
                    [
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name,
                        'tenant_domain' => $tenant->domain,
                        'exception' => $exception->getMessage(),
                    ]
                );
            }
        }

        return $roles;
    }

    /**
     * Collect statistics from the currently active tenant.
     *
     * @return array{
     *     users: int,
     *     patients: int,
     *     lab_tests: int,
     *     test_results: int
     * }
     */
    private function collectTenantStatistics(): array
    {
        return [
            'users' => TenantUser::query()->count(),
            'patients' => Patient::query()->count(),
            'lab_tests' => LabTest::query()->count(),
            'test_results' => TestResult::query()->count(),
        ];
    }
}