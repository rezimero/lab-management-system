<?php

namespace App\Services\Landlord;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class TenantActivityAggregator
{
    public function __construct(
        protected TenantContextRunner $tenantContextRunner,
    ) {
    }

    /**
     * Collect recent user activity across every tenant.
     *
     * @return array{
     *     activities: array<int, array<string, mixed>>,
     *     total: int
     * }
     */
    public function recent(int $limit = 20): array
    {
        $activities = [];

        foreach (Tenant::query()->get() as $tenant) {
            try {
                $tenantActivities = $this->tenantContextRunner->run(
                    $tenant,
                    fn (): array => TenantUser::query()
                        ->orderByDesc('created_at')
                        ->limit(5)
                        ->get()
                        ->map(
                            static fn (TenantUser $user): array => [
                                'type' => 'user_created',
                                'tenant' => $tenant->name,
                                'user' => $user->name,
                                'email' => $user->email,
                                'role' => $user->role,
                                'created_at' => $user->created_at,
                            ]
                        )
                        ->all()
                );

                $activities = array_merge(
                    $activities,
                    $tenantActivities
                );
            } catch (Throwable $exception) {
                Log::error(
                    'Failed to collect recent tenant activity.',
                    [
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->name,
                        'tenant_domain' => $tenant->domain,
                        'exception' => $exception->getMessage(),
                    ]
                );
            }
        }

        usort(
            $activities,
            fn (array $first, array $second): int =>
                $this->timestamp($second['created_at'])
                <=>
                $this->timestamp($first['created_at'])
        );

        $activities = array_slice(
            $activities,
            0,
            $limit
        );

        return [
            'activities' => $activities,
            'total' => count($activities),
        ];
    }

    /**
     * Convert an activity date into a sortable Unix timestamp.
     */
    private function timestamp(mixed $value): int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        $timestamp = strtotime((string) $value);

        return $timestamp === false
            ? 0
            : $timestamp;
    }
}