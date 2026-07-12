<?php

namespace App\Services\Landlord;

use App\Models\Tenant;

class DashboardService
{
    public function __construct(
        protected TenantStatisticsAggregator $statisticsAggregator,
        protected TenantActivityAggregator $activityAggregator,
    ) {
    }

    /**
     * Return complete platform statistics.
     *
     * @return array<string, mixed>
     */
    public function statistics(): array
    {
        return $this->statisticsAggregator->statistics();
    }

    /**
     * Return summarized platform dashboard information.
     *
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $statistics = $this->statisticsAggregator
            ->statistics();

        return [
            'tenants' => [
                'total' => $statistics['total_tenants'],
                'active' => Tenant::query()
                    ->where('is_active', true)
                    ->count(),
                'suspended' => Tenant::query()
                    ->where('is_active', false)
                    ->count(),
            ],
            'users' => [
                'total' => $statistics['total_users'],
                'by_role' => $this->statisticsAggregator
                    ->userRoles(),
            ],
            'patients' => [
                'total' => $statistics['total_patients'],
            ],
            'lab_tests' => [
                'total' => $statistics['total_lab_tests'],
            ],
            'test_results' => [
                'total' => $statistics['total_test_results'],
            ],
        ];
    }

    /**
     * Return recent platform activity.
     *
     * @return array<string, mixed>
     */
    public function recentActivity(int $limit): array
    {
        return $this->activityAggregator->recent($limit);
    }
}