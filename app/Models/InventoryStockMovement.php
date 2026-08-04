<?php

namespace App\Models;

use App\Models\Concerns\ImmutableInventoryRecord;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InventoryStockMovement extends Model
{
    use ImmutableInventoryRecord;

    public $timestamps = false;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid', 'branch_id', 'inventory_item_id', 'inventory_lot_id',
        'movement_type', 'quantity_delta', 'quantity_after',
        'inventory_receipt_id', 'inventory_adjustment_id',
        'inventory_consumption_id', 'reason', 'occurred_at',
        'performed_by', 'created_at',
    ];

    protected $casts = [
        'quantity_delta' => 'decimal:6',
        'quantity_after' => 'decimal:6',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryStockMovement $movement): void {
            $movement->uuid ??= (string) Str::uuid();
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(InventoryLot::class, 'inventory_lot_id');
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(InventoryReceipt::class, 'inventory_receipt_id');
    }

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(InventoryAdjustment::class, 'inventory_adjustment_id');
    }

    public function consumption(): BelongsTo
    {
        return $this->belongsTo(InventoryConsumption::class, 'inventory_consumption_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'performed_by');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
