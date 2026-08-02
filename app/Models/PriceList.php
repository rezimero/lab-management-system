<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PriceList extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'currency',
        'is_default',
        'is_active',
        'effective_from',
        'effective_to',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (PriceList $priceList): void {
            if (blank($priceList->uuid)) {
                $priceList->uuid = (string) Str::uuid();
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }

    public function scopeCurrentlyEffective(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query->whereNull('effective_from')
                    ->orWhereDate('effective_from', '<=', today());
            })
            ->where(function (Builder $query): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', today());
            });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
