<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AnalyzerParameterMapping extends Model
{
    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'analyzer_test_mapping_id',
        'instrument_parameter_code',
        'instrument_parameter_name',
        'target_parameter_name',
        'expected_unit',
        'is_required',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnalyzerParameterMapping $mapping): void {
            $mapping->uuid ??= (string) Str::uuid();
        });
    }

    public function testMapping(): BelongsTo
    {
        return $this->belongsTo(
            AnalyzerTestMapping::class,
            'analyzer_test_mapping_id'
        );
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
