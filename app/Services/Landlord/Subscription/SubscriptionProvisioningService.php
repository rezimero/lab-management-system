<?php

namespace App\Services\Landlord\Subscription;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Facades\DB;

class SubscriptionProvisioningService
{
    public function ensureTrial(Tenant $tenant): TenantSubscription
    {
        return DB::connection('landlord')->transaction(
            function () use ($tenant): TenantSubscription {
                $lockedTenant = Tenant::query()
                    ->whereKey($tenant->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $existing = TenantSubscription::query()
                    ->where('tenant_id', $lockedTenant->getKey())
                    ->latest('starts_at')
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }

                $plan = Plan::query()
                    ->where('is_default', true)
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->firstOrFail();

                $startsAt = now();
                $trialEndsAt = $lockedTenant->trial_ends_at
                    ?? $startsAt->copy()->addDays($plan->trial_days);

                return TenantSubscription::query()->create([
                    'tenant_id' => $lockedTenant->getKey(),
                    'plan_id' => $plan->getKey(),
                    'status' => 'trialing',
                    'billing_interval' => 'monthly',
                    'starts_at' => $startsAt,
                    'trial_ends_at' => $trialEndsAt,
                    'current_period_starts_at' => $startsAt,
                    'current_period_ends_at' => $trialEndsAt,
                    'metadata' => [
                        'source' => 'tenant_provisioning',
                    ],
                ]);
            }
        );
    }
}
