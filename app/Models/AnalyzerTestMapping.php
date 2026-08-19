<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AnalyzerTestMapping extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'analyzer_instrument_id',
        'lab_test_id',
        'instrument_test_code',
        'instrument_test_name',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnalyzerTestMapping $mapping): void {
            $mapping->uuid ??= (string) Str::uuid();
        });
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(
            AnalyzerInstrument::class,
            'analyzer_instrument_id'
        );
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class);
    }

    public function parameterMappings(): HasMany
    {
        return $this->hasMany(AnalyzerParameterMapping::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(AnalyzerResultImport::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'updated_by');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
