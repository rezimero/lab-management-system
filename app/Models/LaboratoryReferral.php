<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use LogicException;

class LaboratoryReferral extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_DISPATCHED = 'dispatched';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_RESULT_RECEIVED = 'result_received';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const TERMINAL_STATUSES = [
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'referral_number',
        'laboratory_order_item_id',
        'referral_laboratory_id',
        'referral_laboratory_test_id',
        'dispatch_branch_id',
        'open_order_item_key',
        'status',
        'laboratory_snapshot',
        'test_snapshot',
        'quoted_cost',
        'final_cost',
        'currency',
        'external_reference',
        'dispatch_notes',
        'turnaround_due_at',
        'created_by',
        'dispatched_by',
        'acknowledged_by',
        'result_received_by',
        'completed_by',
        'rejected_by',
        'cancelled_by',
        'dispatched_at',
        'acknowledged_at',
        'result_received_at',
        'completed_at',
        'rejected_at',
        'cancelled_at',
        'rejection_reason',
        'cancellation_reason',
    ];

    protected $casts = [
        'laboratory_snapshot' => 'array',
        'test_snapshot' => 'array',
        'quoted_cost' => 'decimal:2',
        'final_cost' => 'decimal:2',
        'turnaround_due_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'result_received_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (LaboratoryReferral $referral): void {
            $referral->uuid ??= (string) Str::uuid();
            $referral->status ??= self::STATUS_DRAFT;
            $referral->currency = Str::upper(
                trim((string) ($referral->currency ?: 'NGN'))
            );
        });

        static::updating(function (LaboratoryReferral $referral): void {
            $originalStatus = (string) $referral->getOriginal('status');

            if (in_array($originalStatus, self::TERMINAL_STATUSES, true)) {
                throw new LogicException(
                    'Terminal laboratory referrals are immutable.'
                );
            }

            if ($referral->isDirty([
                'laboratory_order_item_id',
                'referral_laboratory_id',
                'referral_laboratory_test_id',
                'dispatch_branch_id',
                'laboratory_snapshot',
                'test_snapshot',
            ])) {
                throw new LogicException(
                    'Referral ownership and snapshots are immutable.'
                );
            }

            if (
                $originalStatus !== self::STATUS_DRAFT
                && $referral->isDirty(['quoted_cost', 'currency'])
            ) {
                throw new LogicException(
                    'Dispatched referral cost snapshots are immutable.'
                );
            }

            $referral->currency = Str::upper(
                trim((string) ($referral->currency ?: 'NGN'))
            );
        });

        static::deleting(static function (): void {
            throw new LogicException(
                'Laboratory referral workflow evidence cannot be deleted.'
            );
        });
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(
            LaboratoryOrderItem::class,
            'laboratory_order_item_id'
        );
    }

    public function referralLaboratory(): BelongsTo
    {
        return $this->belongsTo(ReferralLaboratory::class);
    }

    public function referralLaboratoryTest(): BelongsTo
    {
        return $this->belongsTo(ReferralLaboratoryTest::class);
    }

    public function dispatchBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'dispatch_branch_id');
    }

    public function specimens(): BelongsToMany
    {
        return $this->belongsToMany(
            Specimen::class,
            'laboratory_referral_specimens',
            'laboratory_referral_id',
            'specimen_id'
        )->withPivot(['specimen_snapshot', 'linked_by'])
            ->withTimestamps();
    }

    public function events(): HasMany
    {
        return $this->hasMany(LaboratoryReferralEvent::class);
    }

    public function externalResults(): HasMany
    {
        return $this->hasMany(LaboratoryReferralResult::class);
    }

    public function latestExternalResult(): HasOne
    {
        return $this->hasOne(LaboratoryReferralResult::class)
            ->ofMany('version', 'max');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'created_by');
    }

    public function dispatchedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'dispatched_by');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'acknowledged_by');
    }

    public function resultReceivedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'result_received_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'completed_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'rejected_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'cancelled_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::TERMINAL_STATUSES);
    }

    public function scopeTerminal(Builder $query): Builder
    {
        return $query->whereIn('status', self::TERMINAL_STATUSES);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
