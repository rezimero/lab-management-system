<?php

namespace App\Services\Tenant\Inventory;

use App\Models\InventoryConsumption;
use App\Models\InventoryLot;
use App\Models\LaboratoryOrderItem;
use App\Models\Tenant\User as TenantUser;
use App\Support\Inventory\InventoryDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryConsumptionService
{
    public function __construct(
        protected InventoryBranchAccessService $branchAccess,
        protected InventoryNumberService $numbers,
        protected StockPostingService $stockPosting
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function post(
        LaboratoryOrderItem $orderItem,
        array $data,
        TenantUser $actor
    ): InventoryConsumption {
        return DB::connection('tenant')->transaction(
            function () use ($orderItem, $data, $actor): InventoryConsumption {
                $orderItem->loadMissing(['order.processingBranch', 'labTest']);

                if (
                    $orderItem->status === 'cancelled'
                    || $orderItem->order?->status === 'cancelled'
                ) {
                    throw ValidationException::withMessages([
                        'laboratory_order_item' => [
                            'Inventory consumption cannot be posted to a cancelled laboratory order item.',
                        ],
                    ]);
                }

                $branch = $orderItem->order?->processingBranch;

                if ($branch === null) {
                    throw ValidationException::withMessages([
                        'laboratory_order_item' => ['The order item has no processing laboratory branch.'],
                    ]);
                }

                $this->branchAccess->authorize($actor, $branch, true, true);
                $consumedAt = CarbonImmutable::parse($data['consumed_at']);

                $consumption = InventoryConsumption::query()->create([
                    'consumption_number' => $this->numbers->consumptionNumber(),
                    'laboratory_order_item_id' => $orderItem->getKey(),
                    'branch_id' => $branch->getKey(),
                    'consumed_at' => $consumedAt,
                    'notes' => $data['notes'] ?? null,
                    'recorded_by' => $actor->getKey(),
                    'created_at' => now(),
                ]);

                foreach ($data['lines'] as $index => $line) {
                    $lot = InventoryLot::query()
                        ->with('receipt')
                        ->where('uuid', $line['lot_uuid'])
                        ->lockForUpdate()
                        ->first();

                    if ($lot === null || (int) $lot->receipt->branch_id !== (int) $branch->getKey()) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.lot_uuid" => ['Select a lot available at the processing branch.'],
                        ]);
                    }

                    if ($lot->expires_at !== null && $lot->expires_at->isBefore($consumedAt->startOfDay())) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.lot_uuid" => ['Expired inventory lots cannot be consumed.'],
                        ]);
                    }

                    $this->stockPosting->post(
                        $branch,
                        $lot,
                        'consumption',
                        InventoryDecimal::negate($line['quantity']),
                        $consumedAt,
                        $actor,
                        ['inventory_consumption_id' => $consumption->getKey()],
                        'Laboratory test consumption'
                    );
                }

                return $consumption->fresh([
                    'orderItem.order', 'orderItem.labTest', 'branch', 'recorder',
                    'movements.item.category', 'movements.item.unit',
                    'movements.lot',
                ]);
            }, 3
        );
    }
}
