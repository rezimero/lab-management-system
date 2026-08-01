<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use LogicException;

class LaboratoryReport extends Model
{
    use HasFactory;

    /**
     * Tenant database connection.
     */
    protected $connection = 'tenant';

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'uuid',
        'lineage_uuid',
        'version',
        'report_number',
        'laboratory_order_id',
        'supersedes_report_id',
        'generated_by',
        'laboratory_snapshot',
        'patient_snapshot',
        'order_snapshot',
        'results_snapshot',
        'amendment_reason',
        'amendment_summary',
        'amended_by',
        'amendment_actor_snapshot',
        'amended_at',
        'generated_at',
    ];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'uuid' => 'string',
        'lineage_uuid' => 'string',
        'version' => 'integer',
        'laboratory_snapshot' => 'array',
        'patient_snapshot' => 'array',
        'order_snapshot' => 'array',
        'results_snapshot' => 'array',
        'amendment_summary' => 'array',
        'amendment_actor_snapshot' => 'array',
        'amended_at' => 'datetime',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Generate UUIDs and prevent issued-report mutation.
     */
    protected static function booted(): void
    {
        static::creating(
            function (
                LaboratoryReport $report
            ): void {
                if (blank($report->uuid)) {
                    $report->uuid = (
                        (string) Str::uuid()
                    );
                }

                if (blank($report->lineage_uuid)) {
                    $report->lineage_uuid = $report->uuid;
                }

                if ($report->version === null) {
                    $report->version = 1;
                }
            }
        );

        static::updating(
            static function (): void {
                throw new LogicException(
                    'Issued laboratory reports are immutable.'
                );
            }
        );

        static::deleting(
            static function (): void {
                throw new LogicException(
                    'Issued laboratory reports cannot be deleted.'
                );
            }
        );
    }

    /**
     * Source laboratory order.
     */
    public function laboratoryOrder(): BelongsTo
    {
        return $this->belongsTo(
            LaboratoryOrder::class,
            'laboratory_order_id'
        );
    }

    /**
     * Immediately preceding report version.
     */
    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'supersedes_report_id'
        );
    }

    /**
     * Immediately succeeding report version.
     */
    public function supersededBy(): HasOne
    {
        return $this->hasOne(
            self::class,
            'supersedes_report_id'
        );
    }

    /**
     * Every report in this immutable lineage.
     */
    public function lineageReports(): HasMany
    {
        return $this->hasMany(
            self::class,
            'lineage_uuid',
            'lineage_uuid'
        );
    }

    /**
     * Amendment requests submitted against this report.
     */
    public function amendmentRequests(): HasMany
    {
        return $this->hasMany(
            LaboratoryReportAmendment::class,
            'source_report_id'
        );
    }

    /**
     * Workflow request that issued this report version.
     */
    public function approvedAmendment(): HasOne
    {
        return $this->hasOne(
            LaboratoryReportAmendment::class,
            'target_report_id'
        );
    }

    /**
     * Tenant user who generated the report.
     */
    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(
            TenantUser::class,
            'generated_by'
        );
    }

    /**
     * Reviewer who authorized an amended report.
     */
    public function amendedBy(): BelongsTo
    {
        return $this->belongsTo(
            TenantUser::class,
            'amended_by'
        );
    }

    /**
     * Stable original report number for this lineage.
     */
    public function baseReportNumber(): string
    {
        if ($this->version <= 1) {
            return (string) $this->report_number;
        }

        $base = preg_replace(
            '/-A[1-9][0-9]*$/',
            '',
            (string) $this->report_number
        );

        return is_string($base) && $base !== ''
            ? $base
            : (string) $this->report_number;
    }

    /**
     * Amendment sequence number, where the original is zero.
     */
    public function amendmentNumber(): int
    {
        return max(0, ((int) $this->version) - 1);
    }

    /**
     * Use UUID for public route binding.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
