<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Upload media to any model.
     */
    public function upload(
        Model $model,
        UploadedFile $file,
        string $collection,
        string $disk = 'public'
    ): Media {

        return DB::transaction(function () use (
            $model,
            $file,
            $collection,
            $disk
        ) {

            $directory = sprintf(
                '%s/%s/%s',
                Str::kebab(class_basename($model)),
                $model->getKey(),
                $collection
            );

            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            $path = $file->storeAs(
                $directory,
                $filename,
                $disk
            );

            return $model->media()->create([

                'collection' => $collection,

                'disk' => $disk,

                'path' => $path,

                'file_name' => $file->getClientOriginalName(),

                'mime_type' => $file->getMimeType(),

                'extension' => strtolower(
                    $file->getClientOriginalExtension()
                ),

                'size' => $file->getSize(),
            ]);
        });
    }

    /**
     * Replace an existing collection.
     */
    public function replace(
        Model $model,
        UploadedFile $file,
        string $collection,
        string $disk = 'public'
    ): Media {

        $this->deleteCollection(
            $model,
            $collection
        );

        return $this->upload(
            $model,
            $file,
            $collection,
            $disk
        );
    }

    /**
     * Delete one media file.
     */
    public function delete(Media $media): void
    {
        DB::transaction(function () use ($media) {

            if (
                Storage::disk($media->disk)
                    ->exists($media->path)
            ) {
                Storage::disk($media->disk)
                    ->delete($media->path);
            }

            $media->delete();
        });
    }

    /**
     * Delete an entire media collection.
     */
    public function deleteCollection(
        Model $model,
        string $collection
    ): void {

        $model->media()
            ->where('collection', $collection)
            ->get()
            ->each(function (Media $media) {

                $this->delete($media);

            });
    }

    /**
     * Get the first media item in a collection.
     */
    public function first(
        Model $model,
        string $collection
    ): ?Media {

        return $model->media()
            ->where('collection', $collection)
            ->latest()
            ->first();
    }

    /**
     * Get all media in a collection.
     */
    public function all(
        Model $model,
        string $collection
    ) {
        return $model->media()
            ->where('collection', $collection)
            ->latest()
            ->get();
    }

    /**
     * Determine whether a collection has media.
     */
    public function has(
        Model $model,
        string $collection
    ): bool {

        return $model->media()
            ->where('collection', $collection)
            ->exists();
    }

    /**
     * Generate a unique storage directory.
     */
    public function generateDirectory(
        Model $model,
        string $collection
    ): string {

        return sprintf(
            '%s/%s/%s',
            Str::kebab(class_basename($model)),
            $model->getKey(),
            $collection
        );
    }
}