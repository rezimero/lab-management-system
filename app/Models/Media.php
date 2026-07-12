<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'mediaable_type',
        'mediaable_id',

        'collection',

        'disk',

        'path',

        'file_name',

        'mime_type',

        'extension',

        'size',
    ];

    protected $casts = [
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function mediaable()
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

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
            return round($bytes / 1073741824, 2) . ' GB';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
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

    /*
    |--------------------------------------------------------------------------
    | Model Events
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::deleting(function (Media $media) {

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