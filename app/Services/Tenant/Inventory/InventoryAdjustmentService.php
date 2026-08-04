<?php

namespace App\Services\Tenant\Inventory;

use App\Models\Branch;
use App\Models\InventoryAdjustment;
use App\Models\InventoryLot;
use App\Models\Tenant\User as TenantUser;
use App\Support\Inventory\InventoryDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryAdjustmentService
{
    public function __construct(
        protected InventoryBranchAccessService $branchAccess,
        protected InventoryNumberService $numbers,
        protected StockPostingService $stockPosting
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function post(array $data, TenantUser $actor): InventoryAdjustment
    {
        return DB::connection('tenant')->transaction(
            function () use ($data, $actor): InventoryAdjustment {
                $branch = Branch::query()
                    ->where('uuid', $data['branch_uuid'])
                    ->firstOrFail();
                $this->branchAccess->authorize($actor, $branch, true);
                $adjustedAt = CarbonImmutable::parse($data['adjusted_at']);

                $adjustment = InventoryAdjustment::query()->create([
                    'adjustment_number' => $this->numbers->adjustmentNumber(),
                    'branch_id' => $branch->getKey(),
                    'reason' => trim($data['reason']),
                    'notes' => $data['notes'] ?? null,
                    'adjusted_at' => $adjustedAt,
                    'created_by' => $actor->getKey(),
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
                            "lines.{$index}.lot_uuid" => ['Select a lot received at the chosen branch.'],
                        ]);
                    }

                    $direction = $line['direction'];
                    $delta = $direction === 'in'
                        ? $line['quantity']
                        : InventoryDecimal::negate($line['quantity']);

                    $this->stockPosting->post(
                        $branch,
                        $lot,
                        $direction === 'in' ? 'adjustment_in' : 'adjustment_out',
                        $delta,
                        $adjustedAt,
                        $actor,
                        ['inventory_adjustment_id' => $adjustment->getKey()],
                        $adjustment->reason
                    );
                }

                return $adjustment->fresh([
                    'branch', 'creator',
                    'movements.item.category', 'movements.item.unit',
                    'movements.lot',
                ]);
            }, 3
        );
    }
}
