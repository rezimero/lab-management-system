<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ReferralLaboratory extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'accreditation_number',
        'contact_name',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'country',
        'currency',
        'default_turnaround_hours',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'default_turnaround_hours' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ReferralLaboratory $laboratory): void {
            $laboratory->uuid ??= (string) Str::uuid();
            $laboratory->normalizeIdentifiers();
        });

        static::updating(function (ReferralLaboratory $laboratory): void {
            $laboratory->normalizeIdentifiers();
        });
    }

    public function testMappings(): HasMany
    {
        return $this->hasMany(ReferralLaboratoryTest::class);
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
        $this->code = Str::upper(trim((string) $this->code));
        $this->currency = Str::upper(
            trim((string) ($this->currency ?: 'NGN'))
        );
    }
}
