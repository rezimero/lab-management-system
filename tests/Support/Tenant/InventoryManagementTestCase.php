<?php

namespace Tests\Support\Tenant;

use App\Models\Branch;
use App\Models\InventoryLot;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

abstract class InventoryManagementTestCase extends LaboratoryOrderManagementTestCase
{
    /**
     * @return array<string, mixed>
     */
    protected function authenticatedInventoryContext(
        string $prefix = 'inventory'
    ): array {
        return $this->authenticatedLaboratoryOrderContext($prefix);
    }

    protected function headOffice(Tenant $tenant): Branch
    {
        return $this->runInTenant(
            $tenant,
            static fn (): Branch => Branch::query()
                ->where('is_head_office', true)
                ->firstOrFail()
        );
    }

    /**
     * @return array{category_uuid: string, unit_uuid: string, item_uuid: string}
     */
    protected function createInventoryCatalogue(
        array $headers,
        string $branchUuid,
        string $suffix = 'BASE'
    ): array {
        $categoryUuid = $this->withHeaders($headers)
            ->postJson('/api/inventory/categories', [
                'code' => 'CAT-'.$suffix,
                'name' => 'Inventory Category '.$suffix,
                'is_active' => true,
            ])
            ->assertCreated()
            ->json('data.uuid');

        $unitUuid = $this->withHeaders($headers)
            ->postJson('/api/inventory/units', [
                'code' => 'UNIT-'.$suffix,
                'name' => 'Inventory Unit '.$suffix,
                'symbol' => 'u',
                'decimal_places' => 6,
                'is_active' => true,
            ])
            ->assertCreated()
            ->json('data.uuid');

        $itemUuid = $this->withHeaders($headers)
            ->postJson('/api/inventory/items', [
                'sku' => 'SKU-'.$suffix,
                'name' => 'Inventory Item '.$suffix,
                'item_type' => 'reagent',
                'category_uuid' => $categoryUuid,
                'unit_uuid' => $unitUuid,
                'tracks_lots' => true,
                'tracks_expiry' => true,
                'default_reorder_level' => '5.000000',
                'is_active' => true,
                'branches' => [[
                    'branch_uuid' => $branchUuid,
                    'reorder_level' => '4.000000',
                    'is_active' => true,
                ]],
            ])
            ->assertCreated()
            ->json('data.uuid');

        return [
            'category_uuid' => $categoryUuid,
            'unit_uuid' => $unitUuid,
            'item_uuid' => $itemUuid,
        ];
    }

    /**
     * @return array{receipt_uuid: string, lot_uuid: string}
     */
    protected function receiveInventory(
        array $headers,
        string $branchUuid,
        string $itemUuid,
        string $quantity = '10.000000'
    ): array {
        $response = $this->withHeaders($headers)
            ->postJson('/api/inventory/receipts', [
                'branch_uuid' => $branchUuid,
                'received_at' => now()->toISOString(),
                'notes' => 'Focused inventory test receipt',
                'lines' => [[
                    'item_uuid' => $itemUuid,
                    'external_lot_number' => 'EXT-LOT-001',
                    'batch_number' => 'BATCH-001',
                    'expires_at' => now()->addMonths(6)->toDateString(),
                    'quantity' => $quantity,
                    'unit_cost' => '100.0000',
                    'currency' => 'NGN',
                ]],
            ])
            ->assertCreated();

        return [
            'receipt_uuid' => $response->json('data.uuid'),
            'lot_uuid' => $response->json('data.lots.0.uuid'),
        ];
    }

    protected function tenantInventoryCount(Tenant $tenant, string $table): int
    {
        return $this->runInTenant(
            $tenant,
            static fn (): int => (int) DB::connection('tenant')->table($table)->count()
        );
    }

    protected function findLot(Tenant $tenant, string $uuid): InventoryLot
    {
        return $this->runInTenant(
            $tenant,
            static fn (): InventoryLot => InventoryLot::query()->where('uuid', $uuid)->firstOrFail()
        );
    }
}
