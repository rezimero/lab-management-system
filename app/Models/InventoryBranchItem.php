<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBranchItem extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'branch_id', 'inventory_item_id', 'reorder_level', 'is_active',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'reorder_level' => 'decimal:6',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
