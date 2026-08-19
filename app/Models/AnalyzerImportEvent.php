<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use LogicException;

class AnalyzerImportEvent extends Model
{
    public $timestamps = false;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'analyzer_result_import_id',
        'event_type',
        'from_status',
        'to_status',
        'actor_type',
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
        static::creating(function (AnalyzerImportEvent $event): void {
            $event->uuid ??= (string) Str::uuid();
            $event->occurred_at ??= now();
            $event->created_at ??= now();
        });

        static::updating(function (): void {
            throw new LogicException(
                'Analyzer import events are append-only.'
            );
        });

        static::deleting(function (): void {
            throw new LogicException(
                'Analyzer import events cannot be deleted.'
            );
        });
    }

    public function resultImport(): BelongsTo
    {
        return $this->belongsTo(
            AnalyzerResultImport::class,
            'analyzer_result_import_id'
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
