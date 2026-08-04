<?php

namespace Tests\Feature\Tenant\Inventory;

use App\Models\Plan;
use App\Models\TenantSubscription;
use App\Support\Commercial\CommercialFeatureCatalog;
use Illuminate\Support\Str;
use Tests\Support\Tenant\InventoryManagementTestCase;

class TenantInventoryEntitlementIsolationTest extends InventoryManagementTestCase
{
    public function test_disabled_inventory_entitlement_blocks_mutation_without_writes(): void
    {
        $context = $this->authenticatedInventoryContext('inventory-entitlement');
        $subscription = TenantSubscription::query()
            ->where('tenant_id', $context['tenant']->getKey())
            ->latest('starts_at')
            ->firstOrFail();
        $originalPlanId = $subscription->plan_id;
        $plan = Plan::query()->create([
            'code' => 'inventory-disabled-'.strtolower(Str::random(8)),
            'name' => 'Inventory Disabled Test Plan',
            'currency' => 'NGN',
            'monthly_price' => '0.00',
            'annual_price' => '0.00',
            'trial_days' => 0,
            'is_default' => false,
            'is_public' => false,
            'is_active' => true,
            'sort_order' => 999,
        ]);
        $plan->features()->create([
            'feature_key' => CommercialFeatureCatalog::INVENTORY,
            'name' => 'Inventory and reagent management',
            'is_enabled' => false,
        ]);
        $subscription->update(['plan_id' => $plan->getKey()]);

        try {
            $this->withHeaders($context['headers'])
                ->postJson('/api/inventory/categories', [
                    'code' => 'BLOCKED',
                    'name' => 'Blocked Category',
                ])
                ->assertForbidden();

            $this->assertSame(0, $this->tenantInventoryCount(
                $context['tenant'],
                'inventory_categories'
            ));
        } finally {
            $this->restoreLandlordContext();
            $subscription->refresh()->update(['plan_id' => $originalPlanId]);
            $plan->delete();
        }
    }

    public function test_inventory_records_are_isolated_between_tenants(): void
    {
        $first = $this->authenticatedInventoryContext('inventory-tenant-a');
        $firstBranch = $this->headOffice($first['tenant']);
        $catalogue = $this->createInventoryCatalogue(
            $first['headers'],
            $firstBranch->uuid,
            'TENANT-A'
        );
        $received = $this->receiveInventory(
            $first['headers'],
            $firstBranch->uuid,
            $catalogue['item_uuid']
        );

        $second = $this->authenticatedInventoryContext('inventory-tenant-b');

        $this->withHeaders($second['headers'])
            ->getJson('/api/inventory/receipts/'.$received['receipt_uuid'])
            ->assertNotFound();

        $this->assertSame(0, $this->tenantInventoryCount(
            $second['tenant'],
            'inventory_receipts'
        ));
    }
}
