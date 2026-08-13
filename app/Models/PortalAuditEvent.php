<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class PortalAuditEvent extends Model
{
    use UsesTenantConnection;

    protected $connection = 'tenant';

    protected $table = 'portal_audit_events';

    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'event',
        'actor_type',
        'actor_id',
        'actor_uuid',
        'actor_snapshot',
        'subject_type',
        'subject_id',
        'subject_uuid',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'actor_id' => 'integer',
        'actor_snapshot' => 'array',
        'subject_id' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(
            function (PortalAuditEvent $event): void {
                if (blank($event->uuid)) {
                    $event->uuid = (string) Str::uuid();
                }
            }
        );

        static::updating(
            static function (): void {
                throw new LogicException(
                    'Portal audit events are immutable.'
                );
            }
        );

        static::deleting(
            static function (): void {
                throw new LogicException(
                    'Portal audit events cannot be deleted.'
                );
            }
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
