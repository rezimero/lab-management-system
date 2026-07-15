<?php

namespace App\Models\Concerns;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;

trait HasMedia
{
    /**
     * Media relationship.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(
            Media::class,
            'mediaable'
        );
    }

    /**
     * Attach media to this model.
     */
    public function attachMedia(
        UploadedFile $file,
        string $collection,
        string $disk = 'public'
    ): Media {

        return app(MediaService::class)->upload(
            $this,
            $file,
            $collection,
            $disk
        );
    }

    /**
     * Replace media in a collection.
     */
    public function replaceMedia(
        UploadedFile $file,
        string $collection,
        string $disk = 'public'
    ): Media {

        return app(MediaService::class)->replace(
            $this,
            $file,
            $collection,
            $disk
        );
    }

    /**
     * Delete an entire media collection.
     */
    public function clearMediaCollection(
        string $collection
    ): void {

        app(MediaService::class)
            ->deleteCollection(
                $this,
                $collection
            );
    }

    /**
     * Get all media in a collection.
     */
    public function getMedia(
        string $collection
    )
    {
        return $this->media()
            ->where('collection', $collection)
            ->latest()
            ->get();
    }

    /**
     * Get the first media item in a collection.
     */
    public function getFirstMedia(
        string $collection
    ): ?Media {

        return $this->media()
            ->where('collection', $collection)
            ->latest()
            ->first();
    }

    /**
     * Get the public URL of the first media item.
     */
    public function getFirstMediaUrl(
        string $collection
    ): ?string {

        return $this->getFirstMedia($collection)?->url;
    }

    /**
     * Determine whether the collection contains media.
     */
    public function hasMedia(
        string $collection
    ): bool {

        return $this->media()
            ->where('collection', $collection)
            ->exists();
    }
}