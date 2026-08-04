<?php

namespace App\Models;

use App\Models\Concerns\ImmutableInventoryRecord;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class InventoryConsumption extends Model
{
    use ImmutableInventoryRecord;

    public $timestamps = false;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid', 'consumption_number', 'laboratory_order_item_id',
        'branch_id', 'consumed_at', 'notes', 'recorded_by', 'created_at',
    ];

    protected $casts = [
        'consumed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryConsumption $consumption): void {
            $consumption->uuid ??= (string) Str::uuid();
        });
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(LaboratoryOrderItem::class, 'laboratory_order_item_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'recorded_by');
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
