<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;
use RuntimeException;
use Throwable;

class MediaService
{
    /**
     * Upload media to any tenant-owned model.
     */
    public function upload(
        Model $model,
        UploadedFile $file,
        string $collection,
        string $disk = 'public'
    ): Media {
        $tenantId = $this->resolveTenantId(
            $model
        );

        $directory = $this->generateDirectory(
            $model,
            $collection
        );

        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        if ($extension === '') {
            throw new RuntimeException(
                'The uploaded file does not have an extension.'
            );
        }

        $filename = sprintf(
            '%s.%s',
            Str::uuid(),
            $extension
        );

        $path = $file->storeAs(
            $directory,
            $filename,
            $disk
        );

        if (! is_string($path) || $path === '') {
            throw new RuntimeException(
                'The uploaded file could not be stored.'
            );
        }

        $hash = hash_file(
            'sha256',
            $file->getRealPath()
        );

        try {
            return DB::connection('landlord')
                ->transaction(
                    fn (): Media => (
                        $model->media()->create([
                            'tenant_id' => $tenantId,
                            'collection' => $collection,
                            'disk' => $disk,
                            'directory' => $directory,
                            'filename' => $filename,
                            'original_filename' => (
                                $file->getClientOriginalName()
                            ),
                            'extension' => $extension,
                            'mime_type' => (
                                $file->getMimeType()
                                ?: 'application/octet-stream'
                            ),
                            'size' => $file->getSize(),
                            'path' => $path,
                            'hash' => (
                                is_string($hash)
                                    ? $hash
                                    : null
                            ),
                        ])
                    )
                );
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($path);

            throw $exception;
        }
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
    public function delete(
        Media $media
    ): void {
        DB::connection('landlord')
            ->transaction(
                static function () use (
                    $media
                ): void {
                    $media->delete();
                }
            );
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
            ->each(function (Media $media): void {
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

    /**
     * Resolve the landlord tenant owner for a media record.
     */
    private function resolveTenantId(
        Model $model
    ): int {
        $modelTenantId = $model->getAttribute(
            'tenant_id'
        );

        if (
            is_numeric($modelTenantId) &&
            (int) $modelTenantId > 0
        ) {
            return (int) $modelTenantId;
        }

        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            throw new LogicException(
                'No active tenant is available for media storage.'
            );
        }

        return (int) $tenant->id;
    }
}