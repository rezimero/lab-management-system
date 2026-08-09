<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class LabTest extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'category',
        'price',
        'turnaround_time_hours',
        'parameters',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'turnaround_time_hours' => 'integer',
        'parameters' => 'json',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (LabTest $labTest): void {
            if (blank($labTest->uuid)) {
                $labTest->uuid = (string) Str::uuid();
            }
        });
    }

    public function priceListItems(): HasMany
    {
        return $this->hasMany(PriceListItem::class);
    }

    public function inventoryRequirements(): HasMany
    {
        return $this->hasMany(LabTestInventoryRequirement::class);
    }

    public function referralLaboratoryTests(): HasMany
    {
        return $this->hasMany(ReferralLaboratoryTest::class);
    }

    // Relationship with test results
    public function test_results()
    {
        return $this->hasMany(TestResult::class);
    }
}
