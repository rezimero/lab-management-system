<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabTestInventoryRequirement extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'lab_test_id', 'inventory_item_id', 'quantity', 'is_active',
        'notes', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
