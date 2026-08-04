<?php

namespace Tests\Feature\Tenant\Inventory;

use App\Models\InventoryReceipt;
use Illuminate\Support\Str;
use LogicException;
use Tests\Support\Tenant\InventoryManagementTestCase;

class TenantInventoryStockFlowTest extends InventoryManagementTestCase
{
    public function test_receipt_adjustment_and_immutable_ledger_flow(): void
    {
        $context = $this->authenticatedInventoryContext('inventory-stock');
        $branch = $this->headOffice($context['tenant']);
        $catalogue = $this->createInventoryCatalogue(
            $context['headers'],
            $branch->uuid,
            'STOCK'
        );
        $received = $this->receiveInventory(
            $context['headers'],
            $branch->uuid,
            $catalogue['item_uuid'],
            '10.500000'
        );

        $this->withHeaders($context['headers'])
            ->getJson('/api/inventory/stock?per_page=50')
            ->assertOk()
            ->assertJsonPath('data.0.quantity_on_hand', '10.500000')
            ->assertJsonPath('data.0.lot.uuid', $received['lot_uuid']);

        $adjustment = $this->withHeaders($context['headers'])
            ->postJson('/api/inventory/adjustments', [
                'branch_uuid' => $branch->uuid,
                'reason' => 'Damaged reagent write-off',
                'adjusted_at' => now()->toISOString(),
                'lines' => [[
                    'lot_uuid' => $received['lot_uuid'],
                    'direction' => 'out',
                    'quantity' => '1.500000',
                ]],
            ])
            ->assertCreated();

        $adjustment->assertJsonPath('data.movements.0.quantity_after', '9.000000');

        $this->withHeaders($context['headers'])
            ->getJson('/api/inventory/movements?per_page=50')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.movement_type', 'adjustment_out')
            ->assertJsonPath('data.1.movement_type', 'receipt');

        $this->expectException(LogicException::class);
        $this->runInTenant($context['tenant'], function () use ($received): void {
            InventoryReceipt::query()
                ->where('uuid', $received['receipt_uuid'])
                ->firstOrFail()
                ->update(['notes' => 'Illegal mutation']);
        });
    }

    public function test_receipt_posting_rolls_back_all_lines_when_one_line_is_invalid(): void
    {
        $context = $this->authenticatedInventoryContext('inventory-receipt-atomic');
        $branch = $this->headOffice($context['tenant']);
        $catalogue = $this->createInventoryCatalogue(
            $context['headers'],
            $branch->uuid,
            'RECEIPT-ATOMIC'
        );

        $this->withHeaders($context['headers'])
            ->postJson('/api/inventory/receipts', [
                'branch_uuid' => $branch->uuid,
                'received_at' => now()->toISOString(),
                'lines' => [
                    [
                        'item_uuid' => $catalogue['item_uuid'],
                        'expires_at' => now()->addMonths(3)->toDateString(),
                        'quantity' => '4.000000',
                    ],
                    [
                        'item_uuid' => (string) Str::uuid(),
                        'expires_at' => now()->addMonths(3)->toDateString(),
                        'quantity' => '2.000000',
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lines.1.item_uuid');

        $this->assertSame(0, $this->tenantInventoryCount(
            $context['tenant'],
            'inventory_receipts'
        ));
        $this->assertSame(0, $this->tenantInventoryCount(
            $context['tenant'],
            'inventory_lots'
        ));
        $this->assertSame(0, $this->tenantInventoryCount(
            $context['tenant'],
            'inventory_stock_balances'
        ));
        $this->assertSame(0, $this->tenantInventoryCount(
            $context['tenant'],
            'inventory_stock_movements'
        ));
    }

    public function test_low_stock_and_expiry_warnings_are_classified(): void
    {
        $context = $this->authenticatedInventoryContext('inventory-alerts');
        $branch = $this->headOffice($context['tenant']);
        $catalogue = $this->createInventoryCatalogue(
            $context['headers'],
            $branch->uuid,
            'ALERTS'
        );

        $this->withHeaders($context['headers'])
            ->postJson('/api/inventory/receipts', [
                'branch_uuid' => $branch->uuid,
                'received_at' => now()->toISOString(),
                'lines' => [[
                    'item_uuid' => $catalogue['item_uuid'],
                    'expires_at' => now()->addDays(10)->toDateString(),
                    'quantity' => '3.000000',
                ]],
            ])
            ->assertCreated();

        $this->withHeaders($context['headers'])
            ->getJson('/api/inventory/alerts?expiry_days=30')
            ->assertOk()
            ->assertJsonPath('data.summary.low_stock', 1)
            ->assertJsonPath('data.summary.out_of_stock', 0)
            ->assertJsonPath('data.summary.expiring_soon', 1)
            ->assertJsonPath('data.summary.expired', 0)
            ->assertJsonPath('data.low_stock.0.item.uuid', $catalogue['item_uuid'])
            ->assertJsonPath('data.expiring.0.quantity_on_hand', '3.000000');
    }

    public function test_outbound_adjustment_cannot_create_negative_stock(): void
    {
        $context = $this->authenticatedInventoryContext('inventory-negative');
        $branch = $this->headOffice($context['tenant']);
        $catalogue = $this->createInventoryCatalogue(
            $context['headers'],
            $branch->uuid,
            'NEGATIVE'
        );
        $received = $this->receiveInventory(
            $context['headers'],
            $branch->uuid,
            $catalogue['item_uuid'],
            '2.000000'
        );

        $this->withHeaders($context['headers'])
            ->postJson('/api/inventory/adjustments', [
                'branch_uuid' => $branch->uuid,
                'reason' => 'Invalid excessive adjustment',
                'adjusted_at' => now()->toISOString(),
                'lines' => [[
                    'lot_uuid' => $received['lot_uuid'],
                    'direction' => 'out',
                    'quantity' => '2.000001',
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lines');

        $this->assertSame(0, $this->tenantInventoryCount(
            $context['tenant'],
            'inventory_adjustments'
        ));
        $this->assertSame(1, $this->tenantInventoryCount(
            $context['tenant'],
            'inventory_stock_movements'
        ));
    }
}
