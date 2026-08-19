<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class TestResult extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

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
        'laboratory_order_item_id',
        'results_data',
        'notes',
        'status',
        'entered_by',
        'submitted_by',
        'reviewed_by',
        'submitted_at',
        'reviewed_at',
        'rejection_reason',
    ];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'uuid' => 'string',
        'results_data' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Generate the public UUID.
     */
    protected static function booted(): void
    {
        static::creating(
            function (TestResult $result): void {
                if (blank($result->uuid)) {
                    $result->uuid = (
                        (string) Str::uuid()
                    );
                }
            }
        );
    }

    /**
     * Order item represented by this result.
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(
            LaboratoryOrderItem::class,
            'laboratory_order_item_id'
        );
    }

    /**
     * User who initially entered the result.
     */
    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'entered_by'
        );
    }

    /**
     * User who submitted the result.
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'submitted_by'
        );
    }

    /**
     * User who approved or rejected the result.
     */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by'
        );
    }

    /**
     * Approved append-only corrections.
     */
    public function amendments(): HasMany
    {
        return $this->hasMany(
            TestResultAmendment::class,
            'test_result_id'
        );
    }

    /**
     * Latest approved effective correction.
     */
    public function latestAmendment(): HasOne
    {
        return $this->hasOne(
            TestResultAmendment::class,
            'test_result_id'
        )->ofMany(
            'version',
            'max'
        );
    }

    /**
     * External referral evidence that originated this result.
     */
    public function originatingReferralResult(): HasOne
    {
        return $this->hasOne(
            LaboratoryReferralResult::class,
            'test_result_id'
        )->ofMany('version', 'max');
    }


    /**
     * Analyzer import evidence that originated this draft/result.
     */
    public function originatingAnalyzerImport(): HasOne
    {
        return $this->hasOne(
            AnalyzerResultImport::class,
            'test_result_id'
        )->latestOfMany();
    }

    /**
     * Legacy patient relationship.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(
            Patient::class
        );
    }

    /**
     * Legacy test-catalogue relationship.
     */
    public function labTest(): BelongsTo
    {
        return $this->belongsTo(
            LabTest::class
        );
    }

    /**
     * Legacy result-entry user relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    /**
     * Use UUID for public route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
