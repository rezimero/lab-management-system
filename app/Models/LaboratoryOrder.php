<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LaboratoryOrder extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Tenant database connection.
     */
    protected $connection = 'tenant';

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'uuid',
        'order_number',
        'patient_id',
        'ordered_by',
        'referring_clinician',
        'priority',
        'status',
        'clinical_notes',
        'total_amount',
        'ordered_at',
        'sample_collected_at',
        'completed_at',
        'cancelled_at',
    ];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'uuid' => 'string',
        'total_amount' => 'decimal:2',
        'ordered_at' => 'datetime',
        'sample_collected_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Generate the public UUID.
     */
    protected static function booted(): void
    {
        static::creating(
            function (
                LaboratoryOrder $order
            ): void {
                if (blank($order->uuid)) {
                    $order->uuid = (
                        (string) Str::uuid()
                    );
                }
            }
        );
    }

    /**
     * Patient attached to the order.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(
            Patient::class
        );
    }

    /**
     * User who created the order.
     */
    public function orderedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'ordered_by'
        );
    }

    /**
     * Tests included in the order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(
            LaboratoryOrderItem::class
        );
    }

    /**
     * Specimens collected for the order.
     */
    public function specimens(): HasMany
    {
        return $this->hasMany(
            Specimen::class
        );
    }

    /**
     * Current immutable report for this order.
     */
    public function report(): HasOne
    {
        return $this->hasOne(
            LaboratoryReport::class,
            'laboratory_order_id'
        )->ofMany(
            'version',
            'max'
        );
    }

    /**
     * Every immutable report version for this order.
     */
    public function reports(): HasMany
    {
        return $this->hasMany(
            LaboratoryReport::class,
            'laboratory_order_id'
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
