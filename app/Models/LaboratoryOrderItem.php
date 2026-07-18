<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class LaboratoryOrderItem extends Model
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
        'laboratory_order_id',
        'lab_test_id',
        'test_name_snapshot',
        'test_category_snapshot',
        'price_snapshot',
        'turnaround_time_hours_snapshot',
        'parameters_snapshot',
        'status',
        'processing_started_by',
        'processing_started_at',
        'completed_at',
    ];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'uuid' => 'string',
        'price_snapshot' => 'decimal:2',
        'turnaround_time_hours_snapshot' => (
            'integer'
        ),
        'parameters_snapshot' => 'array',
        'processing_started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Generate the public UUID.
     */
    protected static function booted(): void
    {
        static::creating(
            function (
                LaboratoryOrderItem $item
            ): void {
                if (blank($item->uuid)) {
                    $item->uuid = (
                        (string) Str::uuid()
                    );
                }
            }
        );
    }

    /**
     * Parent laboratory order.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(
            LaboratoryOrder::class,
            'laboratory_order_id'
        );
    }

    /**
     * Current test-catalogue record.
     */
    public function labTest(): BelongsTo
    {
        return $this->belongsTo(
            LabTest::class
        );
    }

    /**
     * Specimens associated with this item.
     */
    public function specimens(): BelongsToMany
    {
        return $this->belongsToMany(
            Specimen::class,
            'laboratory_order_item_specimen',
            'laboratory_order_item_id',
            'specimen_id'
        )->withTimestamps();
    }

    /**
     * Result belonging to this order item.
     */
    public function testResult(): HasOne
    {
        return $this->hasOne(
            TestResult::class,
            'laboratory_order_item_id'
        );
    }

    /**
     * User who started processing the item.
     */
    public function processingStartedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'processing_started_by'
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
