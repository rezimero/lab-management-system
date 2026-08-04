<?php

namespace App\Models;

use App\Models\Concerns\ImmutableReferralEvidence;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class LaboratoryReferralResult extends Model
{
    use ImmutableReferralEvidence;

    public $timestamps = false;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'laboratory_referral_id',
        'version',
        'supersedes_referral_result_id',
        'external_report_number',
        'results_data',
        'notes',
        'reported_at',
        'received_at',
        'received_by',
        'receiver_snapshot',
        'source_payload_hash',
        'test_result_id',
        'created_at',
    ];

    protected $casts = [
        'version' => 'integer',
        'results_data' => 'array',
        'reported_at' => 'datetime',
        'received_at' => 'datetime',
        'receiver_snapshot' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (LaboratoryReferralResult $result): void {
            $result->uuid ??= (string) Str::uuid();
        });
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(
            LaboratoryReferral::class,
            'laboratory_referral_id'
        );
    }

    public function supersededResult(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'supersedes_referral_result_id'
        );
    }

    public function successor(): HasOne
    {
        return $this->hasOne(
            self::class,
            'supersedes_referral_result_id'
        );
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'received_by');
    }

    public function testResult(): BelongsTo
    {
        return $this->belongsTo(TestResult::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
