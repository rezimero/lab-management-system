<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
    ];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'uuid' => 'string',
        'price_snapshot' => 'decimal:2',
        'turnaround_time_hours_snapshot' => 'integer',
        'parameters_snapshot' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Generate the public UUID.
     */
    protected static function booted(): void
    {
        static::creating(
            function (LaboratoryOrderItem $item): void {
                if (blank($item->uuid)) {
                    $item->uuid = (string) Str::uuid();
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
     * Current catalogue record.
     *
     * The relationship may become null after a catalogue
     * record is deleted, while snapshot fields remain.
     */
    public function labTest(): BelongsTo
    {
        return $this->belongsTo(
            LabTest::class
        );
    }
}
