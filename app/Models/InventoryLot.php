<?php

namespace App\Models;

use App\Models\Concerns\ImmutableInventoryRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class InventoryLot extends Model
{
    use ImmutableInventoryRecord;

    public $timestamps = false;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid', 'internal_lot_code', 'inventory_receipt_id',
        'inventory_item_id', 'external_lot_number', 'batch_number',
        'manufactured_at', 'expires_at', 'quantity_received',
        'unit_cost', 'currency', 'created_at',
    ];

    protected $casts = [
        'manufactured_at' => 'date',
        'expires_at' => 'date',
        'quantity_received' => 'decimal:6',
        'unit_cost' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryLot $lot): void {
            $lot->uuid ??= (string) Str::uuid();
        });
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(InventoryReceipt::class, 'inventory_receipt_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryStockBalance::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryStockMovement::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
