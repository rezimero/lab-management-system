<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class LaboratoryReportAmendment extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Tenant database connection.
     */
    protected $connection = 'tenant';

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'uuid',
        'source_report_id',
        'open_source_report_id',
        'target_report_id',
        'status',
        'reason',
        'change_summary',
        'proposed_patient_snapshot',
        'proposed_order_snapshot',
        'proposed_results_snapshot',
        'requested_by',
        'requester_snapshot',
        'requested_at',
        'reviewed_by',
        'reviewer_snapshot',
        'reviewed_at',
        'rejection_reason',
    ];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'uuid' => 'string',
        'change_summary' => 'array',
        'proposed_patient_snapshot' => 'array',
        'proposed_order_snapshot' => 'array',
        'proposed_results_snapshot' => 'array',
        'requester_snapshot' => 'array',
        'reviewer_snapshot' => 'array',
        'requested_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Generate UUIDs and protect terminal workflow evidence.
     */
    protected static function booted(): void
    {
        static::creating(
            function (
                LaboratoryReportAmendment $amendment
            ): void {
                if (blank($amendment->uuid)) {
                    $amendment->uuid = (
                        (string) Str::uuid()
                    );
                }

                if (
                    $amendment->status !== self::STATUS_SUBMITTED
                    || $amendment->source_report_id === null
                    || $amendment->open_source_report_id
                        !== $amendment->source_report_id
                    || $amendment->target_report_id !== null
                    || $amendment->requested_by === null
                    || ! is_array(
                        $amendment->requester_snapshot
                    )
                    || $amendment->requester_snapshot === []
                    || $amendment->requested_at === null
                    || $amendment->reviewed_by !== null
                    || $amendment->reviewer_snapshot !== null
                    || $amendment->reviewed_at !== null
                    || $amendment->rejection_reason !== null
                ) {
                    throw new LogicException(
                        'A new amendment must contain complete submitted-state evidence.'
                    );
                }
            }
        );

        static::updating(
            function (
                LaboratoryReportAmendment $amendment
            ): void {
                if (
                    $amendment->getOriginal('status')
                    !== self::STATUS_SUBMITTED
                ) {
                    throw new LogicException(
                        'Terminal laboratory report amendments are immutable.'
                    );
                }

                $targetStatus = $amendment->status;

                if (
                    ! in_array(
                        $targetStatus,
                        [
                            self::STATUS_APPROVED,
                            self::STATUS_REJECTED,
                        ],
                        true
                    )
                ) {
                    throw new LogicException(
                        'A submitted amendment must become approved or rejected.'
                    );
                }

                $allowed = [
                    'status',
                    'open_source_report_id',
                    'target_report_id',
                    'reviewed_by',
                    'reviewer_snapshot',
                    'reviewed_at',
                    'rejection_reason',
                    'updated_at',
                ];

                $unexpected = array_diff(
                    array_keys($amendment->getDirty()),
                    $allowed
                );

                if ($unexpected !== []) {
                    throw new LogicException(
                        'Submitted amendment evidence cannot be changed during review.'
                    );
                }

                if ($amendment->open_source_report_id !== null) {
                    throw new LogicException(
                        'A terminal amendment cannot retain an open source report.'
                    );
                }

                if (
                    $targetStatus === self::STATUS_APPROVED
                    && $amendment->target_report_id === null
                ) {
                    throw new LogicException(
                        'An approved amendment requires a target report.'
                    );
                }

                if (
                    $targetStatus === self::STATUS_REJECTED
                    && $amendment->target_report_id !== null
                ) {
                    throw new LogicException(
                        'A rejected amendment cannot reference a target report.'
                    );
                }

                if (
                    $amendment->reviewed_by === null
                    || ! is_array(
                        $amendment->reviewer_snapshot
                    )
                    || $amendment->reviewer_snapshot === []
                    || $amendment->reviewed_at === null
                ) {
                    throw new LogicException(
                        'A terminal amendment requires complete reviewer evidence.'
                    );
                }

                if (
                    $targetStatus === self::STATUS_APPROVED
                    && $amendment->rejection_reason !== null
                ) {
                    throw new LogicException(
                        'An approved amendment cannot contain a rejection reason.'
                    );
                }

                if (
                    $targetStatus === self::STATUS_REJECTED
                    && (
                        ! is_string(
                            $amendment->rejection_reason
                        )
                        || mb_strlen(
                            trim($amendment->rejection_reason)
                        ) < 10
                    )
                ) {
                    throw new LogicException(
                        'A rejected amendment requires a meaningful rejection reason.'
                    );
                }
            }
        );

        static::deleting(
            static function (): void {
                throw new LogicException(
                    'Laboratory report amendment evidence cannot be deleted.'
                );
            }
        );
    }

    public function sourceReport(): BelongsTo
    {
        return $this->belongsTo(
            LaboratoryReport::class,
            'source_report_id'
        );
    }

    public function openSourceReport(): BelongsTo
    {
        return $this->belongsTo(
            LaboratoryReport::class,
            'open_source_report_id'
        );
    }

    public function targetReport(): BelongsTo
    {
        return $this->belongsTo(
            LaboratoryReport::class,
            'target_report_id'
        );
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(
            TenantUser::class,
            'requested_by'
        );
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(
            TenantUser::class,
            'reviewed_by'
        );
    }

    public function resultAmendments(): HasMany
    {
        return $this->hasMany(
            TestResultAmendment::class,
            'laboratory_report_amendment_id'
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
