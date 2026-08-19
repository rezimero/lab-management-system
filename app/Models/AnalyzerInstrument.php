<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AnalyzerInstrument extends Model
{
    public const ADAPTER_CANONICAL_JSON_V1 = 'canonical_json_v1';

    public const TRANSPORT_GATEWAY = 'gateway';

    public const TRANSPORT_DIRECT_HTTP = 'direct_http';

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'branch_id',
        'code',
        'name',
        'manufacturer',
        'model',
        'serial_number',
        'adapter_key',
        'transport_mode',
        'machine_token_hash',
        'machine_token_hint',
        'token_rotated_at',
        'is_active',
        'last_seen_at',
        'last_success_at',
        'last_error_at',
        'last_error_code',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'machine_token_hash',
    ];

    protected $casts = [
        'token_rotated_at' => 'datetime',
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'last_success_at' => 'datetime',
        'last_error_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnalyzerInstrument $instrument): void {
            $instrument->uuid ??= (string) Str::uuid();
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function testMappings(): HasMany
    {
        return $this->hasMany(AnalyzerTestMapping::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AnalyzerMessage::class);
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
