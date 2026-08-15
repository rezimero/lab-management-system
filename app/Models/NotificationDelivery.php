<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class NotificationDelivery extends Model
{
    use UsesTenantConnection;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_RETRYING = 'retrying';

    public const STATUS_SENT = 'sent';

    public const STATUS_SIMULATED = 'simulated';

    public const STATUS_FAILED = 'failed';

    public const STATUS_SUPPRESSED = 'suppressed';

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'notification_message_id',
        'idempotency_key',
        'recipient_type',
        'recipient_id',
        'recipient_uuid',
        'channel',
        'destination_hash',
        'destination',
        'payload',
        'status',
        'attempts',
        'max_attempts',
        'next_attempt_at',
        'processing_started_at',
        'sent_at',
        'failed_at',
        'provider',
        'provider_message_id',
        'last_error_code',
        'last_error',
    ];

    protected $hidden = [
        'destination',
        'destination_hash',
        'payload',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'recipient_id' => 'integer',
            'destination' => 'encrypted',
            'payload' => 'encrypted:array',
            'attempts' => 'integer',
            'max_attempts' => 'integer',
            'next_attempt_at' => 'datetime',
            'processing_started_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(
            function (NotificationDelivery $delivery): void {
                if (blank($delivery->uuid)) {
                    $delivery->uuid = (string) Str::uuid();
                }
            }
        );
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(
            NotificationMessage::class,
            'notification_message_id'
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
