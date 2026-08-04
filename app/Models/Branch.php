<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Branch extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'type',
        'parent_branch_id',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'is_head_office',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_head_office' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Branch $branch): void {
            if (blank($branch->uuid)) {
                $branch->uuid = (string) Str::uuid();
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_branch_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_branch_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            TenantUser::class,
            'branch_user',
            'branch_id',
            'user_id'
        )->withPivot(['is_primary', 'assigned_by'])
            ->withTimestamps();
    }

    public function collectionOrders(): HasMany
    {
        return $this->hasMany(LaboratoryOrder::class, 'collection_branch_id');
    }

    public function processingOrders(): HasMany
    {
        return $this->hasMany(LaboratoryOrder::class, 'processing_branch_id');
    }

    public function inventoryBranchItems(): HasMany
    {
        return $this->hasMany(InventoryBranchItem::class);
    }

    public function inventoryStockBalances(): HasMany
    {
        return $this->hasMany(InventoryStockBalance::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
