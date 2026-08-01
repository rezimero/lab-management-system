<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use LogicException;

class TestResultAmendment extends Model
{
    use HasFactory;

    /**
     * Tenant database connection.
     */
    protected $connection = 'tenant';

    /**
     * Append-only rows have no updated_at column.
     */
    public $timestamps = false;

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'uuid',
        'laboratory_report_amendment_id',
        'test_result_id',
        'version',
        'supersedes_test_result_amendment_id',
        'source_payload_hash',
        'corrected_results_data',
        'corrected_notes',
        'reason',
        'amended_by',
        'amendment_actor_snapshot',
        'approved_by',
        'approval_actor_snapshot',
        'approved_at',
        'created_at',
    ];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'uuid' => 'string',
        'version' => 'integer',
        'corrected_results_data' => 'array',
        'amendment_actor_snapshot' => 'array',
        'approval_actor_snapshot' => 'array',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Generate UUIDs and preserve approved corrections.
     */
    protected static function booted(): void
    {
        static::creating(
            function (
                TestResultAmendment $amendment
            ): void {
                if (blank($amendment->uuid)) {
                    $amendment->uuid = (
                        (string) Str::uuid()
                    );
                }

                $isFirstCorrection = (
                    (int) $amendment->version === 2
                );

                if (
                    (int) $amendment->version < 2
                    || (
                        $isFirstCorrection
                        && $amendment
                            ->supersedes_test_result_amendment_id
                            !== null
                    )
                    || (
                        ! $isFirstCorrection
                        && $amendment
                            ->supersedes_test_result_amendment_id
                            === null
                    )
                    || ! is_string(
                        $amendment->source_payload_hash
                    )
                    || preg_match(
                        '/^[a-f0-9]{64}$/',
                        $amendment->source_payload_hash
                    ) !== 1
                    || ! is_array(
                        $amendment->corrected_results_data
                    )
                    || ! is_string($amendment->reason)
                    || mb_strlen(trim($amendment->reason)) < 10
                    || ! is_array(
                        $amendment->amendment_actor_snapshot
                    )
                    || $amendment->amendment_actor_snapshot === []
                    || $amendment->approved_by === null
                    || ! is_array(
                        $amendment->approval_actor_snapshot
                    )
                    || $amendment->approval_actor_snapshot === []
                    || $amendment->approved_at === null
                ) {
                    throw new LogicException(
                        'Approved result-amendment evidence is incomplete or invalid.'
                    );
                }
            }
        );

        static::updating(
            static function (): void {
                throw new LogicException(
                    'Approved test result amendments are immutable.'
                );
            }
        );

        static::deleting(
            static function (): void {
                throw new LogicException(
                    'Approved test result amendments cannot be deleted.'
                );
            }
        );
    }

    public function reportAmendment(): BelongsTo
    {
        return $this->belongsTo(
            LaboratoryReportAmendment::class,
            'laboratory_report_amendment_id'
        );
    }

    public function testResult(): BelongsTo
    {
        return $this->belongsTo(
            TestResult::class,
            'test_result_id'
        );
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'supersedes_test_result_amendment_id'
        );
    }

    public function supersededBy(): HasOne
    {
        return $this->hasOne(
            self::class,
            'supersedes_test_result_amendment_id'
        );
    }

    public function amendedBy(): BelongsTo
    {
        return $this->belongsTo(
            TenantUser::class,
            'amended_by'
        );
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(
            TenantUser::class,
            'approved_by'
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
