<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Plan extends Model
{
    protected $connection = 'landlord';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'description',
        'currency',
        'monthly_price',
        'annual_price',
        'trial_days',
        'is_default',
        'is_public',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'trial_days' => 'integer',
        'is_default' => 'boolean',
        'is_public' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Plan $plan): void {
            if (blank($plan->uuid)) {
                $plan->uuid = (string) Str::uuid();
            }
        });
    }

    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
