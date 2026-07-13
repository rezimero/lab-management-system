<?php

namespace App\Services\Landlord\Tenant;

use App\Models\LabTest;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Models\TestResult;
use App\Services\Landlord\TenantContextRunner;
use Illuminate\Support\Facades\Log;
use Throwable;

class LandlordTenantStatisticsService
{
    /**
     * @var array{
     *     users: int,
     *     patients: int,
     *     lab_tests: int,
     *     test_results: int
     * }
     */
    private const EMPTY_STATS = [
        'users' => 0,
        'patients' => 0,
        'lab_tests' => 0,
        'test_results' => 0,
    ];

    public function __construct(
        protected TenantContextRunner $contextRunner,
    ) {
    }

    /**
     * Collect tenant statistics and propagate failures.
     *
     * @return array{
     *     users: int,
     *     patients: int,
     *     lab_tests: int,
     *     test_results: int
     * }
     */
    public function collect(Tenant $tenant): array
    {
        return $this->contextRunner->run(
            $tenant,
            static fn (): array => [
                'users' => TenantUser::query()->count(),
                'patients' => Patient::query()->count(),
                'lab_tests' => LabTest::query()->count(),
                'test_results' => TestResult::query()->count(),
            ]
        );
    }

    /**
     * Return the tenant representation used by index, show and mutations.
     *
     * Statistics failures retain the existing zero-count fallback.
     *
     * @return array<string, mixed>
     */
    public function tenantWithStats(Tenant $tenant): array
    {
        try {
            $stats = $this->collect($tenant);
        } catch (Throwable $exception) {
            Log::error('Failed to collect tenant statistics.', [
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'tenant_database' => $tenant->database,
                'error' => $exception->getMessage(),
            ]);

            $stats = self::EMPTY_STATS;
        }

        return array_merge(
            $tenant->toArray(),
            [
                'stats' => $stats,
            ]
        );
    }
}