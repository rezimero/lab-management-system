<?php

namespace App\Services\Tenant\Inventory;

use App\Models\InventoryAdjustment;
use App\Models\InventoryBranchItem;
use App\Models\InventoryCategory;
use App\Models\InventoryConsumption;
use App\Models\InventoryItem;
use App\Models\InventoryReceipt;
use App\Models\InventoryStockBalance;
use App\Models\InventoryStockMovement;
use App\Models\InventoryUnit;
use App\Models\LaboratoryOrderItem;
use App\Models\LabTest;
use App\Models\Supplier;
use App\Models\Tenant\User as TenantUser;
use App\Support\Inventory\InventoryDecimal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class InventoryQueryService
{
    public function __construct(
        protected InventoryBranchAccessService $branchAccess
    ) {}

    public function categories(?string $search, ?bool $active, int $perPage): LengthAwarePaginator
    {
        return InventoryCategory::query()
            ->withCount('items')
            ->when($search, fn ($query, $value) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%")
            ))
            ->when($active !== null, fn ($query) => $query->where('is_active', $active))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function units(?string $search, ?bool $active, int $perPage): LengthAwarePaginator
    {
        return InventoryUnit::query()
            ->withCount('items')
            ->when($search, fn ($query, $value) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%")
                    ->orWhere('symbol', 'like', "%{$value}%")
            ))
            ->when($active !== null, fn ($query) => $query->where('is_active', $active))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function suppliers(?string $search, ?bool $active, int $perPage): LengthAwarePaginator
    {
        return Supplier::query()
            ->withCount('receipts')
            ->when($search, fn ($query, $value) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$value}%")
                    ->orWhere('code', 'like', "%{$value}%")
                    ->orWhere('contact_name', 'like', "%{$value}%")
            ))
            ->when($active !== null, fn ($query) => $query->where('is_active', $active))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function items(TenantUser $user, array $filters, int $perPage): LengthAwarePaginator
    {
        $assignedBranchIds = $this->branchAccess->isAdministrator($user)
            ? null
            : $this->branchAccess->assignedBranchIds($user);

        return InventoryItem::query()
            ->with([
                'category',
                'unit',
                'branchSettings' => fn ($query) => $query->when(
                    $assignedBranchIds !== null,
                    fn ($query) => $query->whereIn('branch_id', $assignedBranchIds)
                ),
                'branchSettings.branch',
            ])
            ->when($assignedBranchIds !== null, function ($query) use ($assignedBranchIds): void {
                $query->whereHas('branchSettings', fn ($settings) => $settings
                    ->whereIn('branch_id', $assignedBranchIds));
            })
            ->when($filters['search'] ?? null, fn ($query, $value) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$value}%")
                    ->orWhere('sku', 'like', "%{$value}%")
                    ->orWhere('manufacturer', 'like', "%{$value}%")
                    ->orWhere('catalogue_number', 'like', "%{$value}%")
            ))
            ->when($filters['category_uuid'] ?? null, fn ($query, $value) => $query->whereHas(
                'category', fn ($category) => $category->where('uuid', $value)
            ))
            ->when($filters['item_type'] ?? null, fn ($query, $value) => $query->where('item_type', $value))
            ->when(array_key_exists('active', $filters), fn ($query) => $query->where('is_active', (bool) $filters['active']))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function item(TenantUser $user, InventoryItem $item): InventoryItem
    {
        $assignedBranchIds = $this->branchAccess->isAdministrator($user)
            ? null
            : $this->branchAccess->assignedBranchIds($user);

        if ($assignedBranchIds !== null) {
            $visible = $item->branchSettings()
                ->whereIn('branch_id', $assignedBranchIds)
                ->exists();

            abort_unless($visible, 404);
        }

        return $item->load([
            'category',
            'unit',
            'branchSettings' => fn ($query) => $query->when(
                $assignedBranchIds !== null,
                fn ($query) => $query->whereIn('branch_id', $assignedBranchIds)
            ),
            'branchSettings.branch',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function stock(TenantUser $user, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InventoryStockBalance::query()
            ->with(['branch', 'item.category', 'item.unit', 'lot.receipt.supplier']);
        $this->branchAccess->scopeVisible($query, $user);

        return $query
            ->when($filters['branch_uuid'] ?? null, fn ($query, $value) => $query->whereHas(
                'branch', fn ($branch) => $branch->where('uuid', $value)
            ))
            ->when($filters['category_uuid'] ?? null, fn ($query, $value) => $query->whereHas(
                'item.category', fn ($category) => $category->where('uuid', $value)
            ))
            ->when($filters['item_type'] ?? null, fn ($query, $value) => $query->whereHas(
                'item', fn ($item) => $item->where('item_type', $value)
            ))
            ->when($filters['search'] ?? null, fn ($query, $value) => $query->where(
                fn ($query) => $query->whereHas('item', fn ($item) => $item
                    ->where('name', 'like', "%{$value}%")
                    ->orWhere('sku', 'like', "%{$value}%"))
                    ->orWhereHas('lot', fn ($lot) => $lot
                        ->where('internal_lot_code', 'like', "%{$value}%")
                        ->orWhere('external_lot_number', 'like', "%{$value}%")
                        ->orWhere('batch_number', 'like', "%{$value}%"))
            ))
            ->orderByDesc('last_movement_at')
            ->paginate($perPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function alerts(TenantUser $user, int $expiryDays = 30): array
    {
        $settings = InventoryBranchItem::query()
            ->with(['branch', 'item.category', 'item.unit'])
            ->where('is_active', true)
            ->whereHas('branch', fn ($query) => $query->where('is_active', true));
        $this->branchAccess->scopeVisible($settings, $user);

        $balances = InventoryStockBalance::query()
            ->selectRaw('branch_id, inventory_item_id, SUM(quantity_on_hand) as quantity_on_hand')
            ->groupBy('branch_id', 'inventory_item_id');
        $this->branchAccess->scopeVisible($balances, $user);
        $balanceMap = $balances->get()->keyBy(
            fn ($row): string => $row->branch_id.':'.$row->inventory_item_id
        );

        $low = collect();
        $out = collect();

        foreach ($settings->get() as $setting) {
            $quantity = (string) ($balanceMap->get(
                $setting->branch_id.':'.$setting->inventory_item_id
            )?->quantity_on_hand ?? '0.000000');
            $threshold = (string) ($setting->reorder_level ?? $setting->item->default_reorder_level);
            $row = [
                'branch' => $setting->branch,
                'item' => $setting->item,
                'quantity_on_hand' => $quantity,
                'reorder_level' => $threshold,
            ];

            if (InventoryDecimal::compare($quantity, '0') <= 0) {
                $out->push($row);
            } elseif (InventoryDecimal::compare($quantity, $threshold) <= 0) {
                $low->push($row);
            }
        }

        $expiringQuery = InventoryStockBalance::query()
            ->with(['branch', 'item.category', 'item.unit', 'lot'])
            ->where('quantity_on_hand', '>', 0)
            ->whereHas('lot', fn ($lot) => $lot
                ->whereNotNull('expires_at')
                ->whereDate('expires_at', '<=', now()->addDays($expiryDays)->toDateString()));
        $this->branchAccess->scopeVisible($expiringQuery, $user);
        $expiring = $expiringQuery
            ->limit(200)
            ->get()
            ->sortBy(fn (InventoryStockBalance $balance) => $balance->lot?->expires_at)
            ->values();
        $today = today()->startOfDay();
        $expiredCount = $expiring->filter(
            fn (InventoryStockBalance $balance): bool => $balance->lot?->expires_at?->isBefore($today) ?? false
        )->count();

        return [
            'summary' => [
                'low_stock' => $low->count(),
                'out_of_stock' => $out->count(),
                'expiring_soon' => $expiring->count() - $expiredCount,
                'expired' => $expiredCount,
            ],
            'low_stock' => $low->values(),
            'out_of_stock' => $out->values(),
            'expiring' => $expiring,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function movements(TenantUser $user, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InventoryStockMovement::query()
            ->with(['branch', 'item.category', 'item.unit', 'lot', 'performer']);
        $this->branchAccess->scopeVisible($query, $user);

        return $this->operationFilters($query, $filters)
            ->when($filters['movement_type'] ?? null, fn ($query, $value) => $query->where('movement_type', $value))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function receipts(TenantUser $user, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InventoryReceipt::query()
            ->with(['branch', 'supplier', 'receiver'])
            ->withCount('lots');
        $this->branchAccess->scopeVisible($query, $user);

        return $this->operationFilters($query, $filters, 'received_at')
            ->when($filters['supplier_uuid'] ?? null, fn ($query, $value) => $query->whereHas(
                'supplier', fn ($supplier) => $supplier->where('uuid', $value)
            ))
            ->orderByDesc('received_at')
            ->paginate($perPage);
    }

    public function receipt(TenantUser $user, InventoryReceipt $receipt): InventoryReceipt
    {
        $this->ensureVisibleBranch($user, (int) $receipt->branch_id);

        return $receipt->load([
            'branch', 'supplier', 'receiver',
            'lots.item.category', 'lots.item.unit', 'movements.lot',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adjustments(TenantUser $user, array $filters, int $perPage): LengthAwarePaginator
    {
        $query = InventoryAdjustment::query()->with(['branch', 'creator'])->withCount('movements');
        $this->branchAccess->scopeVisible($query, $user);

        return $this->operationFilters($query, $filters, 'adjusted_at')
            ->orderByDesc('adjusted_at')
            ->paginate($perPage);
    }

    public function adjustment(TenantUser $user, InventoryAdjustment $adjustment): InventoryAdjustment
    {
        $this->ensureVisibleBranch($user, (int) $adjustment->branch_id);

        return $adjustment->load([
            'branch', 'creator', 'movements.item.category',
            'movements.item.unit', 'movements.lot',
        ]);
    }

    public function consumption(TenantUser $user, InventoryConsumption $consumption): InventoryConsumption
    {
        $this->ensureVisibleBranch($user, (int) $consumption->branch_id, true);

        return $consumption->load([
            'orderItem.order', 'orderItem.labTest', 'branch', 'recorder',
            'movements.item.category', 'movements.item.unit', 'movements.lot',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function orderItemConsumptionContext(
        TenantUser $user,
        LaboratoryOrderItem $orderItem
    ): array {
        $orderItem->loadMissing(['order.processingBranch', 'labTest.inventoryRequirements.item.unit']);
        $branch = $orderItem->order?->processingBranch;

        if ($branch === null) {
            throw ValidationException::withMessages([
                'laboratory_order_item' => ['The order item has no processing branch.'],
            ]);
        }

        $this->branchAccess->authorize($user, $branch, false, true);

        $balances = InventoryStockBalance::query()
            ->with(['item.category', 'item.unit', 'lot'])
            ->where('branch_id', $branch->getKey())
            ->where('quantity_on_hand', '>', 0)
            ->whereHas('lot', fn ($lot) => $lot
                ->whereNull('expires_at')
                ->orWhereDate('expires_at', '>=', today()->toDateString()))
            ->orderBy('inventory_item_id')
            ->get();

        $consumptions = InventoryConsumption::query()
            ->with(['recorder', 'movements.item.unit', 'movements.lot'])
            ->where('laboratory_order_item_id', $orderItem->getKey())
            ->orderByDesc('consumed_at')
            ->get();

        return [
            'order_item' => $orderItem,
            'branch' => $branch,
            'suggestions' => $orderItem->labTest?->inventoryRequirements
                ?->where('is_active', true)->values() ?? collect(),
            'available_lots' => $balances,
            'consumptions' => $consumptions,
        ];
    }

    public function labTestByUuid(string $uuid): LabTest
    {
        return LabTest::query()->where('uuid', $uuid)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function operationFilters(
        Builder $query,
        array $filters,
        string $dateColumn = 'occurred_at'
    ): Builder {
        return $query
            ->when($filters['branch_uuid'] ?? null, fn ($query, $value) => $query->whereHas(
                'branch', fn ($branch) => $branch->where('uuid', $value)
            ))
            ->when($filters['date_from'] ?? null, fn ($query, $value) => $query->whereDate($dateColumn, '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($query, $value) => $query->whereDate($dateColumn, '<=', $value));
    }

    private function ensureVisibleBranch(
        TenantUser $user,
        int $branchId,
        bool $allowParent = false
    ): void {
        if ($this->branchAccess->isAdministrator($user)) {
            return;
        }

        $assigned = $this->branchAccess->assignedBranchIds($user);
        if (in_array($branchId, $assigned, true)) {
            return;
        }

        if ($allowParent && $user->branches->contains(
            fn ($branch) => (int) $branch->parent_branch_id === $branchId
        )) {
            return;
        }

        abort(404);
    }
}
