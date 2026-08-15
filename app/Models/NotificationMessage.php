<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class NotificationMessage extends Model
{
    use UsesTenantConnection;

    public const RECIPIENT_TENANT_USER = 'tenant_user';

    public const RECIPIENT_PATIENT_PORTAL = 'patient_portal_account';

    public const RECIPIENT_CLINICIAN_PORTAL = 'clinician_portal_account';

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'idempotency_key',
        'recipient_type',
        'recipient_id',
        'recipient_uuid',
        'category',
        'event_key',
        'priority',
        'title',
        'body',
        'action_url',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'recipient_id' => 'integer',
            'data' => 'array',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(
            function (NotificationMessage $message): void {
                if (blank($message->uuid)) {
                    $message->uuid = (string) Str::uuid();
                }
            }
        );
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(
            NotificationDelivery::class,
            'notification_message_id'
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
