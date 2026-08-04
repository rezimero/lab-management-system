<?php

namespace App\Models;

use App\Models\Concerns\ImmutableReferralEvidence;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LaboratoryReferralEvent extends Model
{
    use ImmutableReferralEvidence;

    public $timestamps = false;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'laboratory_referral_id',
        'event_type',
        'from_status',
        'to_status',
        'actor_id',
        'actor_snapshot',
        'payload',
        'occurred_at',
        'created_at',
    ];

    protected $casts = [
        'actor_snapshot' => 'array',
        'payload' => 'array',
        'occurred_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (LaboratoryReferralEvent $event): void {
            $event->uuid ??= (string) Str::uuid();
        });
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(
            LaboratoryReferral::class,
            'laboratory_referral_id'
        );
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'actor_id');
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
