<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AnalyzerResultImport extends Model
{
    public const STATUS_RECEIVED = 'received';

    public const STATUS_NEEDS_REVIEW = 'needs_review';

    public const STATUS_READY = 'ready';

    public const STATUS_IMPORTED = 'imported';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_FAILED = 'failed';

    public const STATUS_DUPLICATE = 'duplicate';

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'analyzer_message_id',
        'analyzer_instrument_id',
        'analyzer_test_mapping_id',
        'specimen_id',
        'laboratory_order_item_id',
        'test_result_id',
        'source_result_key',
        'source_sample_identifier',
        'instrument_test_code',
        'status',
        'normalized_results',
        'mapping_snapshot',
        'validation_issues',
        'attempts',
        'last_processed_at',
        'accepted_by',
        'accepted_at',
        'rejected_by',
        'rejected_at',
        'review_note',
    ];

    protected $casts = [
        'normalized_results' => 'array',
        'mapping_snapshot' => 'array',
        'validation_issues' => 'array',
        'attempts' => 'integer',
        'last_processed_at' => 'datetime',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (AnalyzerResultImport $import): void {
            $import->uuid ??= (string) Str::uuid();
        });
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(
            AnalyzerMessage::class,
            'analyzer_message_id'
        );
    }

    public function instrument(): BelongsTo
    {
        return $this->belongsTo(
            AnalyzerInstrument::class,
            'analyzer_instrument_id'
        );
    }

    public function testMapping(): BelongsTo
    {
        return $this->belongsTo(
            AnalyzerTestMapping::class,
            'analyzer_test_mapping_id'
        );
    }

    public function specimen(): BelongsTo
    {
        return $this->belongsTo(Specimen::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(
            LaboratoryOrderItem::class,
            'laboratory_order_item_id'
        );
    }

    public function testResult(): BelongsTo
    {
        return $this->belongsTo(TestResult::class);
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'accepted_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(TenantUser::class, 'rejected_by');
    }

    public function events(): HasMany
    {
        return $this->hasMany(
            AnalyzerImportEvent::class,
            'analyzer_result_import_id'
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
