<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Supplier extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid', 'code', 'name', 'contact_name', 'email', 'phone',
        'address', 'tax_identifier', 'is_active', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Supplier $supplier): void {
            $supplier->uuid ??= (string) Str::uuid();
        });
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(InventoryReceipt::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
