<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InventoryItem extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid', 'sku', 'name', 'item_type', 'inventory_category_id',
        'inventory_unit_id', 'manufacturer', 'catalogue_number',
        'description', 'tracks_lots', 'tracks_expiry',
        'default_reorder_level', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'tracks_lots' => 'boolean',
        'tracks_expiry' => 'boolean',
        'default_reorder_level' => 'decimal:6',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryItem $item): void {
            $item->uuid ??= (string) Str::uuid();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(InventoryUnit::class, 'inventory_unit_id');
    }

    public function branchSettings(): HasMany
    {
        return $this->hasMany(InventoryBranchItem::class);
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(
            Branch::class,
            'inventory_branch_items',
            'inventory_item_id',
            'branch_id'
        )->withPivot(['reorder_level', 'is_active'])->withTimestamps();
    }

    public function lots(): HasMany
    {
        return $this->hasMany(InventoryLot::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
