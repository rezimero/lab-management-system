<?php

namespace App\Services\Tenant;

use App\Models\Tenant;
use App\Models\TenantSubscription;

class EntitlementService
{
    public function currentSubscription(
        Tenant $tenant
    ): ?TenantSubscription {
        return TenantSubscription::query()
            ->with('plan.features')
            ->where('tenant_id', $tenant->getKey())
            ->whereIn('status', ['trialing', 'active'])
            ->where(function ($query): void {
                $query->whereNull('current_period_ends_at')
                    ->orWhere('current_period_ends_at', '>=', now());
            })
            ->latest('starts_at')
            ->first();
    }

    /**
     * @return array<string, array{
     *     enabled: bool,
     *     limit: int|null,
     *     name: string
     * }>
     */
    public function features(Tenant $tenant): array
    {
        $subscription = $this->currentSubscription($tenant);

        if ($subscription === null || $subscription->plan === null) {
            return [];
        }

        return $subscription->plan->features
            ->mapWithKeys(
                static fn ($feature): array => [
                    $feature->feature_key => [
                        'enabled' => (bool) $feature->is_enabled,
                        'limit' => $feature->limit_value === null
                            ? null
                            : (int) $feature->limit_value,
                        'name' => $feature->name,
                    ],
                ]
            )
            ->all();
    }

    public function allows(
        Tenant $tenant,
        string $featureKey
    ): bool {
        return (bool) (
            $this->features($tenant)[$featureKey]['enabled']
            ?? false
        );
    }

    public function ensure(
        Tenant $tenant,
        string $featureKey
    ): void {
        if (! $this->allows($tenant, $featureKey)) {
            abort(
                403,
                'This feature is not enabled for the current subscription.'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(Tenant $tenant): array
    {
        $subscription = $this->currentSubscription($tenant);

        return [
            'subscription' => $subscription === null
                ? null
                : [
                    'uuid' => $subscription->uuid,
                    'status' => $subscription->status,
                    'billing_interval' => $subscription->billing_interval,
                    'starts_at' => $subscription->starts_at?->toISOString(),
                    'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
                    'current_period_ends_at' => $subscription
                        ->current_period_ends_at?->toISOString(),
                    'plan' => [
                        'uuid' => $subscription->plan->uuid,
                        'code' => $subscription->plan->code,
                        'name' => $subscription->plan->name,
                        'currency' => $subscription->plan->currency,
                    ],
                ],
            'features' => $this->features($tenant),
        ];
    }
}
