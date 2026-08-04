<?php

namespace Tests\Feature\Landlord\Commercial;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Tests\Support\Landlord\TenantManagementTestCase;

class LandlordSubscriptionCommercialFlowTest extends TenantManagementTestCase
{
    public function test_provisioned_tenant_receives_beta_entitlements_and_can_be_reassigned(): void
    {
        $headers = $this->superadminHeaders();
        $domain = $this->uniqueManagedDomain('subscription');
        $this->trackManagedTenantDomain($domain);

        $tenantResponse = $this
            ->withHeaders($headers)
            ->postJson('/api/landlord/tenants', [
                'name' => 'Subscription Commercial Laboratory',
                'domain' => $domain,
                'admin_name' => 'Subscription Administrator',
                'admin_email' => $domain.'@example.test',
                'admin_password' => 'Password123!',
            ]);

        $tenantResponse->assertCreated();
        $this->restoreLandlordContext();

        $tenant = Tenant::query()
            ->where('domain', $domain)
            ->firstOrFail();
        $this->trackManagedTenantDatabase($tenant->database);

        $subscription = TenantSubscription::query()
            ->with('plan.features')
            ->where('tenant_id', $tenant->getKey())
            ->latest('starts_at')
            ->firstOrFail();

        $this->assertSame('trialing', $subscription->status);
        $this->assertSame('commercial-beta', $subscription->plan->code);
        $this->assertTrue(
            (bool) $subscription->plan->features
                ->firstWhere('feature_key', 'multi_branch')
                ?->is_enabled
        );
        $this->assertTrue(
            (bool) $subscription->plan->features
                ->firstWhere('feature_key', 'billing')
                ?->is_enabled
        );
        $this->assertTrue(
            (bool) $subscription->plan->features
                ->firstWhere('feature_key', 'inventory')
                ?->is_enabled
        );
        $this->assertTrue(
            (bool) $subscription->plan->features
                ->firstWhere('feature_key', 'referral_labs')
                ?->is_enabled
        );

        $plan = Plan::query()->where('code', 'commercial-beta')->firstOrFail();

        $this->withHeaders($headers)
            ->putJson("/api/landlord/tenants/{$tenant->getKey()}/subscription", [
                'plan_uuid' => $plan->uuid,
                'status' => 'active',
                'billing_interval' => 'monthly',
                'current_period_ends_at' => now()->addMonth()->toISOString(),
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.plan.code', 'commercial-beta');

        $this->assertDatabaseHas('tenant_subscriptions', [
            'tenant_id' => $tenant->getKey(),
            'status' => 'active',
        ], 'landlord');
    }

    public function test_superadmin_can_read_plan_and_subscription_portfolios(): void
    {
        $headers = $this->superadminHeaders();

        $this->withHeaders($headers)
            ->getJson('/api/landlord/plans')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'commercial-beta');

        $this->withHeaders($headers)
            ->getJson('/api/landlord/subscriptions?per_page=25')
            ->assertOk()
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total',
            ]);
    }

    public function test_annual_assignment_defaults_to_a_one_year_period(): void
    {
        $headers = $this->superadminHeaders();
        $domain = $this->uniqueManagedDomain('annual-subscription');
        $this->trackManagedTenantDomain($domain);

        $this->withHeaders($headers)
            ->postJson('/api/landlord/tenants', [
                'name' => 'Annual Subscription Laboratory',
                'domain' => $domain,
                'admin_name' => 'Annual Administrator',
                'admin_email' => $domain.'@example.test',
                'admin_password' => 'Password123!',
            ])
            ->assertCreated();

        $this->restoreLandlordContext();
        $tenant = Tenant::query()
            ->where('domain', $domain)
            ->firstOrFail();
        $this->trackManagedTenantDatabase($tenant->database);
        $plan = Plan::query()
            ->where('code', 'commercial-beta')
            ->firstOrFail();
        $startsAt = now()->startOfSecond();

        $response = $this->withHeaders($headers)
            ->putJson(
                "/api/landlord/tenants/{$tenant->getKey()}/subscription",
                [
                    'plan_uuid' => $plan->uuid,
                    'status' => 'active',
                    'billing_interval' => 'annual',
                    'starts_at' => $startsAt->toISOString(),
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath('data.billing_interval', 'annual');

        $subscription = TenantSubscription::query()
            ->where('tenant_id', $tenant->getKey())
            ->where('status', 'active')
            ->latest('starts_at')
            ->firstOrFail();

        $this->assertTrue(
            $subscription->current_period_ends_at->equalTo(
                $startsAt->copy()->addYear()
            )
        );
    }

    public function test_superadmin_cannot_enable_an_unimplemented_feature(): void
    {
        $headers = $this->superadminHeaders();

        $this->withHeaders($headers)
            ->postJson('/api/landlord/plans', [
                'code' => 'unsupported-feature-plan',
                'name' => 'Unsupported Feature Plan',
                'currency' => 'NGN',
                'monthly_price' => '1000.00',
                'annual_price' => '10000.00',
                'trial_days' => 14,
                'features' => [
                    [
                        'key' => 'multi_branch',
                        'name' => 'Multi-branch and collection centres',
                        'is_enabled' => true,
                    ],
                    [
                        'key' => 'billing',
                        'name' => 'Billing, invoices and payments',
                        'is_enabled' => true,
                    ],
                    [
                        'key' => 'notifications',
                        'name' => 'Notifications',
                        'is_enabled' => true,
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('features.2.is_enabled');
    }
}
