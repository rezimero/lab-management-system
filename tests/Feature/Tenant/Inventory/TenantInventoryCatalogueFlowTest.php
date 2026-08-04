<?php

namespace Tests\Feature\Tenant\Inventory;

use App\Models\Branch;
use Tests\Support\Tenant\InventoryManagementTestCase;

class TenantInventoryCatalogueFlowTest extends InventoryManagementTestCase
{
    public function test_administrator_manages_inventory_catalogue_and_branch_thresholds(): void
    {
        $context = $this->authenticatedInventoryContext('inventory-catalogue');
        $branch = $this->headOffice($context['tenant']);
        $catalogue = $this->createInventoryCatalogue(
            $context['headers'],
            $branch->uuid,
            'CATALOGUE'
        );

        $this->withHeaders($context['headers'])
            ->getJson('/api/inventory/items?per_page=50')
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $catalogue['item_uuid'])
            ->assertJsonPath('data.0.branches.0.branch.uuid', $branch->uuid)
            ->assertJsonPath('data.0.branches.0.reorder_level', '4.000000');

        $this->withHeaders($context['headers'])
            ->putJson('/api/inventory/items/'.$catalogue['item_uuid'], [
                'sku' => 'SKU-CATALOGUE',
                'name' => 'Updated Inventory Reagent',
                'item_type' => 'reagent',
                'category_uuid' => $catalogue['category_uuid'],
                'unit_uuid' => $catalogue['unit_uuid'],
                'tracks_lots' => true,
                'tracks_expiry' => true,
                'default_reorder_level' => '6.000000',
                'is_active' => true,
                'branches' => [[
                    'branch_uuid' => $branch->uuid,
                    'reorder_level' => '5.000000',
                    'is_active' => true,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Inventory Reagent')
            ->assertJsonPath('data.default_reorder_level', '6.000000')
            ->assertJsonPath('data.branches.0.reorder_level', '5.000000');
    }

    public function test_non_administrator_only_sees_assigned_branch_settings(): void
    {
        $context = $this->authenticatedInventoryContext('inventory-branch-visibility');
        $headOffice = $this->headOffice($context['tenant']);
        $secondaryBranch = $this->runInTenant(
            $context['tenant'],
            static fn (): Branch => Branch::query()->create([
                'code' => 'INV-SECONDARY',
                'name' => 'Inventory Secondary Laboratory',
                'type' => 'laboratory',
                'is_head_office' => false,
                'is_active' => true,
            ])
        );
        $catalogue = $this->createInventoryCatalogue(
            $context['headers'],
            $headOffice->uuid,
            'BRANCH-VISIBILITY'
        );

        $this->withHeaders($context['headers'])
            ->putJson('/api/inventory/items/'.$catalogue['item_uuid'], [
                'sku' => 'SKU-BRANCH-VISIBILITY',
                'name' => 'Branch-isolated inventory item',
                'item_type' => 'reagent',
                'category_uuid' => $catalogue['category_uuid'],
                'unit_uuid' => $catalogue['unit_uuid'],
                'tracks_lots' => true,
                'tracks_expiry' => true,
                'default_reorder_level' => '5.000000',
                'is_active' => true,
                'branches' => [
                    [
                        'branch_uuid' => $headOffice->uuid,
                        'reorder_level' => '4.000000',
                        'is_active' => true,
                    ],
                    [
                        'branch_uuid' => $secondaryBranch->uuid,
                        'reorder_level' => '8.000000',
                        'is_active' => true,
                    ],
                ],
            ])
            ->assertOk();

        $technician = $this->createTenantUser($context['tenant'], [
            'email' => 'inventory-branch-technician@example.test',
            'password' => 'password',
            'role' => 'lab_technician',
        ]);
        $this->runInTenant($context['tenant'], function () use ($technician, $headOffice): void {
            $technician->branches()->sync([
                $headOffice->getKey() => ['is_primary' => true],
            ]);
        });

        $token = $this->postJson('/api/login', [
            'domain' => $context['registration']['domain'],
            'email' => $technician->email,
            'password' => 'password',
        ])->assertOk()->json('token');
        $this->restoreLandlordContext();

        $this->withHeaders($this->tenantHeaders(
            $token,
            $context['registration']['domain']
        ))
            ->getJson('/api/inventory/items?per_page=50')
            ->assertOk()
            ->assertJsonCount(1, 'data.0.branches')
            ->assertJsonPath('data.0.branches.0.branch.uuid', $headOffice->uuid)
            ->assertJsonMissing(['uuid' => $secondaryBranch->uuid]);
    }

    public function test_receptionist_cannot_access_inventory_catalogue(): void
    {
        $context = $this->authenticatedInventoryContext('inventory-role');
        $registration = $context['registration'];
        $user = $this->createTenantUser($context['tenant'], [
            'email' => 'inventory-receptionist@example.test',
            'password' => 'password',
            'role' => 'receptionist',
        ]);
        $branch = $this->headOffice($context['tenant']);
        $this->runInTenant($context['tenant'], function () use ($user, $branch): void {
            $user->branches()->sync([
                $branch->getKey() => ['is_primary' => true],
            ]);
        });

        $token = $this->postJson('/api/login', [
            'domain' => $registration['domain'],
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()->json('token');
        $this->restoreLandlordContext();

        $this->withHeaders($this->tenantHeaders($token, $registration['domain']))
            ->getJson('/api/inventory/categories')
            ->assertForbidden();
    }
}
