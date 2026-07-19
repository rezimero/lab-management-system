<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Illuminate\Support\Str;

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
        'report_number',
        'laboratory_order_id',
        'generated_by',
        'laboratory_snapshot',
        'patient_snapshot',
        'order_snapshot',
        'results_snapshot',
        'generated_at',
    ];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'uuid' => 'string',
        'laboratory_snapshot' => 'array',
        'patient_snapshot' => 'array',
        'order_snapshot' => 'array',
        'results_snapshot' => 'array',
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
     * Use UUID for public route binding.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
