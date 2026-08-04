<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class InventoryUnit extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid', 'code', 'name', 'symbol', 'decimal_places',
        'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'decimal_places' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryUnit $unit): void {
            $unit->uuid ??= (string) Str::uuid();
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
