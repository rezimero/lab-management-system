<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use LogicException;

class AnalyzerMessage extends Model
{
    public const STATUS_RECEIVED = 'received';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_FAILED = 'failed';

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'analyzer_instrument_id',
        'idempotency_key',
        'external_message_id',
        'adapter_key',
        'content_type',
        'payload_hash',
        'raw_payload',
        'source_timestamp',
        'received_at',
        'processed_at',
        'status',
        'last_error_code',
        'last_error',
    ];

    protected $hidden = [
        'raw_payload',
    ];

    protected $casts = [
        'source_timestamp' => 'datetime',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'raw_payload' => 'encrypted',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnalyzerMessage $message): void {
            $message->uuid ??= (string) Str::uuid();
            $message->received_at ??= now();
        });

        static::updating(function (AnalyzerMessage $message): void {
            foreach ([
                'analyzer_instrument_id',
                'idempotency_key',
                'external_message_id',
                'adapter_key',
                'content_type',
                'payload_hash',
                'raw_payload',
                'source_timestamp',
                'received_at',
            ] as $field) {
                if ($message->isDirty($field)) {
                    throw new LogicException(
                        'Analyzer source-message evidence is immutable.'
                    );
                }
            }
        });

        static::deleting(function (): void {
            throw new LogicException(
                'Analyzer source-message evidence cannot be deleted.'
            );
        });
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(
            AnalyzerInstrument::class,
            'analyzer_instrument_id'
        );
    }

    public function imports(): HasMany
    {
        return $this->hasMany(
            AnalyzerResultImport::class,
            'analyzer_message_id'
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
