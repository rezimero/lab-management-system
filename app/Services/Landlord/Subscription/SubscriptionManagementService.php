<?php

namespace App\Services\Landlord\Subscription;

use App\Models\Landlord\User as LandlordUser;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionManagementService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function savePlan(
        ?Plan $plan,
        array $data
    ): Plan {
        return DB::connection('landlord')->transaction(
            function () use ($plan, $data): Plan {
                $plan ??= new Plan;

                if ((bool) ($data['is_default'] ?? false)) {
                    Plan::query()
                        ->lockForUpdate()
                        ->get();

                    Plan::query()->update(['is_default' => false]);
                }

                $plan->fill([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'currency' => strtoupper($data['currency']),
                    'monthly_price' => $data['monthly_price'],
                    'annual_price' => $data['annual_price'],
                    'trial_days' => $data['trial_days'],
                    'is_default' => $data['is_default'] ?? false,
                    'is_public' => $data['is_public'] ?? true,
                    'is_active' => $data['is_active'] ?? true,
                    'sort_order' => $data['sort_order'] ?? 0,
                ]);
                $plan->save();

                $featureRows = collect($data['features'] ?? [])
                    ->mapWithKeys(
                        static fn (array $feature): array => [
                            $feature['key'] => [
                                'name' => $feature['name'],
                                'is_enabled' => $feature['is_enabled'],
                                'limit_value' => $feature['limit_value'] ?? null,
                            ],
                        ]
                    );

                foreach ($featureRows as $key => $feature) {
                    $plan->features()->updateOrCreate(
                        ['feature_key' => $key],
                        $feature
                    );
                }

                $plan->features()
                    ->whereNotIn('feature_key', $featureRows->keys())
                    ->delete();

                return $plan->fresh('features');
            }
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function assign(
        Tenant $tenant,
        Plan $plan,
        array $data,
        LandlordUser $actor
    ): TenantSubscription {
        return DB::connection('landlord')->transaction(
            function () use (
                $tenant,
                $plan,
                $data,
                $actor
            ): TenantSubscription {
                $lockedTenant = Tenant::query()
                    ->whereKey($tenant->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $status = $data['status'];
                $startsAt = isset($data['starts_at'])
                    ? Carbon::parse($data['starts_at'])
                    : now();

                $trialEndsAt = isset($data['trial_ends_at'])
                    ? Carbon::parse($data['trial_ends_at'])
                    : (
                        $status === 'trialing'
                            ? $startsAt->copy()->addDays($plan->trial_days)
                            : null
                    );

                $periodEndsAt = isset($data['current_period_ends_at'])
                    ? Carbon::parse($data['current_period_ends_at'])
                    : match (true) {
                        $status === 'trialing' && $trialEndsAt !== null => $trialEndsAt,
                        $data['billing_interval'] === 'annual' => $startsAt->copy()->addYear(),
                        default => $startsAt->copy()->addMonth(),
                    };

                TenantSubscription::query()
                    ->where('tenant_id', $lockedTenant->getKey())
                    ->whereIn('status', ['trialing', 'active', 'past_due'])
                    ->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'updated_by' => $actor->getKey(),
                    ]);

                $subscription = TenantSubscription::query()->create([
                    'tenant_id' => $lockedTenant->getKey(),
                    'plan_id' => $plan->getKey(),
                    'status' => $status,
                    'billing_interval' => $data['billing_interval'],
                    'starts_at' => $startsAt,
                    'trial_ends_at' => $trialEndsAt,
                    'current_period_starts_at' => $startsAt,
                    'current_period_ends_at' => $periodEndsAt,
                    'cancelled_at' => $status === 'cancelled'
                        ? now()
                        : null,
                    'metadata' => $data['metadata'] ?? [
                        'source' => 'landlord_assignment',
                    ],
                    'created_by' => $actor->getKey(),
                    'updated_by' => $actor->getKey(),
                ]);

                $lockedTenant->forceFill([
                    'trial_ends_at' => $subscription->trial_ends_at,
                    'subscription_ends_at' => $subscription
                        ->current_period_ends_at,
                    'is_active' => ! in_array(
                        $status,
                        ['suspended', 'expired'],
                        true
                    ),
                ])->save();

                return $subscription->fresh(['plan.features', 'tenant']);
            }
        );
    }
}
