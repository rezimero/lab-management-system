<?php

namespace App\Services\Tenant\Inventory;

use App\Models\Branch;
use App\Models\InventoryBranchItem;
use App\Models\InventoryItem;
use App\Models\InventoryLot;
use App\Models\InventoryReceipt;
use App\Models\Supplier;
use App\Models\Tenant\User as TenantUser;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryReceiptService
{
    public function __construct(
        protected InventoryBranchAccessService $branchAccess,
        protected InventoryNumberService $numbers,
        protected StockPostingService $stockPosting
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function post(array $data, TenantUser $actor): InventoryReceipt
    {
        return DB::connection('tenant')->transaction(
            function () use ($data, $actor): InventoryReceipt {
                $branch = Branch::query()
                    ->where('uuid', $data['branch_uuid'])
                    ->firstOrFail();
                $this->branchAccess->authorize($actor, $branch, true);

                $supplier = null;
                if (! empty($data['supplier_uuid'])) {
                    $supplier = Supplier::query()
                        ->where('uuid', $data['supplier_uuid'])
                        ->where('is_active', true)
                        ->first();

                    if ($supplier === null) {
                        throw ValidationException::withMessages([
                            'supplier_uuid' => ['Select an active supplier.'],
                        ]);
                    }
                }

                $receivedAt = CarbonImmutable::parse($data['received_at']);
                $receipt = InventoryReceipt::query()->create([
                    'receipt_number' => $this->numbers->receiptNumber(),
                    'branch_id' => $branch->getKey(),
                    'supplier_id' => $supplier?->getKey(),
                    'supplier_reference' => $data['supplier_reference'] ?? null,
                    'received_at' => $receivedAt,
                    'notes' => $data['notes'] ?? null,
                    'received_by' => $actor->getKey(),
                    'created_at' => now(),
                ]);

                foreach ($data['lines'] as $index => $line) {
                    $item = InventoryItem::query()
                        ->where('uuid', $line['item_uuid'])
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->first();

                    if ($item === null) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.item_uuid" => ['Select an active inventory item.'],
                        ]);
                    }

                    $expiresAt = isset($line['expires_at'])
                        ? CarbonImmutable::parse($line['expires_at'])->startOfDay()
                        : null;

                    if ($item->tracks_expiry && $expiresAt === null) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.expires_at" => ['An expiry date is required for this item.'],
                        ]);
                    }

                    if ($expiresAt !== null && $expiresAt->isBefore($receivedAt->startOfDay())) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.expires_at" => ['The expiry date cannot be before the receipt date.'],
                        ]);
                    }

                    if (
                        isset($line['manufactured_at'])
                        && $expiresAt !== null
                        && CarbonImmutable::parse($line['manufactured_at'])->startOfDay()->isAfter($expiresAt)
                    ) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.manufactured_at" => ['The manufacture date cannot be after the expiry date.'],
                        ]);
                    }

                    $assignment = InventoryBranchItem::query()->firstOrCreate(
                        [
                            'branch_id' => $branch->getKey(),
                            'inventory_item_id' => $item->getKey(),
                        ],
                        [
                            'reorder_level' => null,
                            'is_active' => true,
                            'created_by' => $actor->getKey(),
                            'updated_by' => $actor->getKey(),
                        ]
                    );

                    if (! $assignment->is_active) {
                        throw ValidationException::withMessages([
                            "lines.{$index}.item_uuid" => ['This inventory item is inactive at the selected branch.'],
                        ]);
                    }

                    $lot = InventoryLot::query()->create([
                        'internal_lot_code' => $this->numbers->lotCode(),
                        'inventory_receipt_id' => $receipt->getKey(),
                        'inventory_item_id' => $item->getKey(),
                        'external_lot_number' => $line['external_lot_number'] ?? null,
                        'batch_number' => $line['batch_number'] ?? null,
                        'manufactured_at' => $line['manufactured_at'] ?? null,
                        'expires_at' => $expiresAt?->toDateString(),
                        'quantity_received' => $line['quantity'],
                        'unit_cost' => $line['unit_cost'] ?? null,
                        'currency' => strtoupper($line['currency'] ?? 'NGN'),
                        'created_at' => now(),
                    ]);

                    $this->stockPosting->post(
                        $branch,
                        $lot,
                        'receipt',
                        $line['quantity'],
                        $receivedAt,
                        $actor,
                        ['inventory_receipt_id' => $receipt->getKey()],
                        'Stock receipt'
                    );
                }

                return $receipt->fresh([
                    'branch', 'supplier', 'receiver',
                    'lots.item.category', 'lots.item.unit',
                    'movements.lot',
                ]);
            }, 3
        );
    }
}
