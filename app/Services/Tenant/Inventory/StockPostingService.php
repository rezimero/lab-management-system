<?php

namespace App\Services\Tenant\Inventory;

use App\Models\Branch;
use App\Models\InventoryBranchItem;
use App\Models\InventoryLot;
use App\Models\InventoryStockBalance;
use App\Models\InventoryStockMovement;
use App\Models\Tenant\User as TenantUser;
use App\Support\Inventory\InventoryDecimal;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockPostingService
{
    /**
     * @param  array<string, int|null>  $references
     */
    public function post(
        Branch $branch,
        InventoryLot $lot,
        string $movementType,
        string $quantityDelta,
        CarbonInterface $occurredAt,
        TenantUser $actor,
        array $references = [],
        ?string $reason = null
    ): InventoryStockMovement {
        $delta = InventoryDecimal::normalize($quantityDelta);

        if (! InventoryDecimal::isPositive(ltrim($delta, '-'))) {
            throw ValidationException::withMessages([
                'lines' => ['Inventory movement quantities must be greater than zero.'],
            ]);
        }

        if ((int) $lot->inventory_item_id < 1) {
            throw ValidationException::withMessages([
                'lines' => ['The selected lot is not linked to an inventory item.'],
            ]);
        }

        $assignment = InventoryBranchItem::query()
            ->where('branch_id', $branch->getKey())
            ->where('inventory_item_id', $lot->inventory_item_id)
            ->where('is_active', true)
            ->first();

        if ($assignment === null) {
            throw ValidationException::withMessages([
                'lines' => ['The inventory item is not active at the selected branch.'],
            ]);
        }

        if (! $lot->item()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'lines' => ['The selected inventory item is inactive.'],
            ]);
        }

        DB::connection('tenant')
            ->table('inventory_stock_balances')
            ->insertOrIgnore([
                'branch_id' => $branch->getKey(),
                'inventory_item_id' => $lot->inventory_item_id,
                'inventory_lot_id' => $lot->getKey(),
                'quantity_on_hand' => '0.000000',
                'last_movement_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $balance = InventoryStockBalance::query()
            ->where('branch_id', $branch->getKey())
            ->where('inventory_lot_id', $lot->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $next = InventoryDecimal::add(
            (string) $balance->quantity_on_hand,
            $delta
        );

        if (InventoryDecimal::compare($next, '0') < 0) {
            throw ValidationException::withMessages([
                'lines' => ['Insufficient stock is available for the selected lot.'],
            ]);
        }

        $balance->forceFill([
            'quantity_on_hand' => $next,
            'last_movement_at' => $occurredAt,
        ])->save();

        return InventoryStockMovement::query()->create([
            'branch_id' => $branch->getKey(),
            'inventory_item_id' => $lot->inventory_item_id,
            'inventory_lot_id' => $lot->getKey(),
            'movement_type' => $movementType,
            'quantity_delta' => $delta,
            'quantity_after' => $next,
            'inventory_receipt_id' => $references['inventory_receipt_id'] ?? null,
            'inventory_adjustment_id' => $references['inventory_adjustment_id'] ?? null,
            'inventory_consumption_id' => $references['inventory_consumption_id'] ?? null,
            'reason' => $reason,
            'occurred_at' => $occurredAt,
            'performed_by' => $actor->getKey(),
            'created_at' => now(),
        ])->fresh(['branch', 'item.category', 'item.unit', 'lot', 'performer']);
    }
}
