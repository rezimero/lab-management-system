<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Media extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Media metadata is stored centrally in the landlord database.
     */
    protected $connection = 'landlord';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'uploaded_by',

        'mediaable_type',
        'mediaable_id',

        'collection',

        'disk',
        'directory',
        'filename',
        'original_filename',
        'extension',
        'mime_type',
        'size',
        'path',
        'hash',
        'metadata',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'uploaded_by' => 'integer',
        'size' => 'integer',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Polymorphic owner.
     */
    public function mediaable()
    {
        return $this->morphTo();
    }

    /**
     * Public URL.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)
            ->url($this->path);
    }

    /**
     * Human-readable file size.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;

        if ($bytes >= 1073741824) {
            return round(
                $bytes / 1073741824,
                2
            ).' GB';
        }

        if ($bytes >= 1048576) {
            return round(
                $bytes / 1048576,
                2
            ).' MB';
        }

        if ($bytes >= 1024) {
            return round(
                $bytes / 1024,
                2
            ).' KB';
        }

        return $bytes.' B';
    }

    /**
     * Is this an image?
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with(
            $this->mime_type,
            'image/'
        );
    }

    /**
     * Is this a PDF?
     */
    public function getIsPdfAttribute(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Is this a video?
     */
    public function getIsVideoAttribute(): bool
    {
        return str_starts_with(
            $this->mime_type,
            'video/'
        );
    }

    /**
     * Is this an audio file?
     */
    public function getIsAudioAttribute(): bool
    {
        return str_starts_with(
            $this->mime_type,
            'audio/'
        );
    }

    /**
     * Assign public identifiers and remove physical files on deletion.
     */
    protected static function booted(): void
    {
        static::creating(function (Media $media): void {
            if (! $media->uuid) {
                $media->uuid = (string) Str::uuid();
            }
        });

        static::deleting(function (Media $media): void {
            if (
                Storage::disk($media->disk)
                    ->exists($media->path)
            ) {
                Storage::disk($media->disk)
                    ->delete($media->path);
            }
        });
    }
}