<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Specimen extends Model
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
        'replaces_specimen_id',
        'accession_number',
        'specimen_type',
        'container_type',
        'collection_site',
        'volume',
        'volume_unit',
        'status',
        'collected_by',
        'collected_at',
        'received_at',
        'rejected_at',
        'rejection_reason',
        'notes',
    ];

    /**
     * Attribute casts.
     */
    protected $casts = [
        'uuid' => 'string',
        'volume' => 'decimal:2',
        'collected_at' => 'datetime',
        'received_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Generate the public UUID.
     */
    protected static function booted(): void
    {
        static::creating(
            function (Specimen $specimen): void {
                if (blank($specimen->uuid)) {
                    $specimen->uuid = (
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
     * Rejected specimen replaced by this specimen.
     */
    public function replaces(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'replaces_specimen_id'
        );
    }

    /**
     * Direct replacement collected for this specimen.
     */
    public function replacement(): HasOne
    {
        return $this->hasOne(
            self::class,
            'replaces_specimen_id'
        );
    }

    /**
     * User who collected the specimen.
     */
    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'collected_by'
        );
    }

    /**
     * Order items supported by the specimen.
     */
    public function orderItems(): BelongsToMany
    {
        return $this->belongsToMany(
            LaboratoryOrderItem::class,
            'laboratory_order_item_specimen',
            'specimen_id',
            'laboratory_order_item_id'
        )->withTimestamps();
    }

    /**
     * Referral workflows that use this specimen.
     */
    public function laboratoryReferrals(): BelongsToMany
    {
        return $this->belongsToMany(
            LaboratoryReferral::class,
            'laboratory_referral_specimens',
            'specimen_id',
            'laboratory_referral_id'
        )->withPivot(['specimen_snapshot', 'linked_by'])
            ->withTimestamps();
    }

    /**
     * Use UUID for public route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
