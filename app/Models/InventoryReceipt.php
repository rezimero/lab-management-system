<?php

namespace App\Models;

use App\Models\Concerns\ImmutableInventoryRecord;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class InventoryReceipt extends Model
{
    use ImmutableInventoryRecord;

    public $timestamps = false;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid', 'receipt_number', 'branch_id', 'supplier_id',
        'supplier_reference', 'received_at', 'notes', 'received_by', 'created_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryReceipt $receipt): void {
            $receipt->uuid ??= (string) Str::uuid();
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'received_by');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(InventoryLot::class);
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
