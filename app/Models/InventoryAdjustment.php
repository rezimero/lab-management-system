<?php

namespace App\Models;

use App\Models\Concerns\ImmutableInventoryRecord;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class InventoryAdjustment extends Model
{
    use ImmutableInventoryRecord;

    public $timestamps = false;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid', 'adjustment_number', 'branch_id', 'reason', 'notes',
        'adjusted_at', 'created_by', 'created_at',
    ];

    protected $casts = [
        'adjusted_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryAdjustment $adjustment): void {
            $adjustment->uuid ??= (string) Str::uuid();
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
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
