<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class CriticalResultEscalation extends Model
{
    use UsesTenantConnection;

    public const STATUS_OPEN = 'open';

    public const STATUS_ACKNOWLEDGED = 'acknowledged';

    public const STATUS_RESOLVED = 'resolved';

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'source_key',
        'test_result_id',
        'test_result_uuid',
        'order_uuid',
        'critical_count',
        'status',
        'escalation_level',
        'detected_at',
        'acknowledgement_due_at',
        'acknowledged_by',
        'acknowledged_at',
        'acknowledgement_note',
        'resolved_by',
        'resolved_at',
        'resolution_note',
        'last_escalated_at',
    ];

    protected function casts(): array
    {
        return [
            'test_result_id' => 'integer',
            'critical_count' => 'integer',
            'escalation_level' => 'integer',
            'detected_at' => 'datetime',
            'acknowledgement_due_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'resolved_at' => 'datetime',
            'last_escalated_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(
            function (CriticalResultEscalation $escalation): void {
                if (blank($escalation->uuid)) {
                    $escalation->uuid = (string) Str::uuid();
                }
            }
        );
    }

    public function testResult(): BelongsTo
    {
        return $this->belongsTo(TestResult::class);
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(
            TenantUser::class,
            'acknowledged_by'
        );
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(
            TenantUser::class,
            'resolved_by'
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
