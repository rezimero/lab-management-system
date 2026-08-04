<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ReferralLaboratoryTest extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'referral_laboratory_id',
        'lab_test_id',
        'external_test_code',
        'external_test_name',
        'cost',
        'currency',
        'turnaround_hours',
        'specimen_requirements',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'cost' => 'decimal:2',
        'turnaround_hours' => 'integer',
        'specimen_requirements' => 'array',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReferralLaboratoryTest $mapping): void {
            $mapping->uuid ??= (string) Str::uuid();
            $mapping->normalizeIdentifiers();
        });

        static::updating(function (ReferralLaboratoryTest $mapping): void {
            $mapping->normalizeIdentifiers();
        });
    }

    public function referralLaboratory(): BelongsTo
    {
        return $this->belongsTo(ReferralLaboratory::class);
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(LaboratoryReferral::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'updated_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    private function normalizeIdentifiers(): void
    {
        $this->external_test_code = Str::upper(
            trim((string) $this->external_test_code)
        );
        $this->currency = Str::upper(
            trim((string) ($this->currency ?: 'NGN'))
        );
    }
}
