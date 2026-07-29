<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Models\Media;
use App\Models\TenantProfile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class LaboratoryReportMediaSnapshotService
{
    private const REPORT_DISK = 'public';

    private const REPORT_DIRECTORY = 'laboratory-reports';

    /**
     * @var array<int, string>
     */
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * @var array<int, string>
     */
    private const ALLOWED_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'webp',
    ];

    /**
     * Copy report media into immutable report-owned storage.
     *
     * @return array<string, string|null>
     */
    public function snapshot(
        TenantProfile $profile,
        string $reportUuid
    ): array {
        if (! Str::isUuid($reportUuid)) {
            throw new RuntimeException(
                'The report media snapshot requires a valid UUID.'
            );
        }

        return array_merge(
            $this->snapshotCollection(
                $profile,
                'logo',
                'logo',
                $reportUuid
            ),
            $this->snapshotCollection(
                $profile,
                'director_signature',
                'director_signature',
                $reportUuid
            )
        );
    }

    /**
     * Remove copied media after failed report generation.
     */
    public function deleteReportMedia(
        string $reportUuid
    ): void {
        if (! Str::isUuid($reportUuid)) {
            return;
        }

        Storage::disk(self::REPORT_DISK)
            ->deleteDirectory(
                self::REPORT_DIRECTORY.'/'.$reportUuid
            );
    }

    /**
     * @return array<string, string|null>
     */
    private function snapshotCollection(
        TenantProfile $profile,
        string $collection,
        string $keyPrefix,
        string $reportUuid
    ): array {
        $media = $profile->getFirstMedia(
            $collection
        );

        if (! $media instanceof Media) {
            return $this->emptySnapshot(
                $keyPrefix
            );
        }

        $this->assertSupportedMedia(
            $profile,
            $media,
            $collection
        );

        $disk = Storage::disk(
            self::REPORT_DISK
        );

        if (! $disk->exists($media->path)) {
            throw new RuntimeException(
                'The source report image does not exist.'
            );
        }

        $contents = $disk->get(
            $media->path
        );

        if (! is_string($contents) || $contents === '') {
            throw new RuntimeException(
                'The source report image could not be read.'
            );
        }

        $extension = strtolower(
            $media->extension
        );

        $destinationPath = sprintf(
            '%s/%s/%s.%s',
            self::REPORT_DIRECTORY,
            $reportUuid,
            str_replace('_', '-', $keyPrefix),
            $extension
        );

        if ($disk->exists($destinationPath)) {
            throw new RuntimeException(
                'The report media snapshot already exists.'
            );
        }

        if (! $disk->put($destinationPath, $contents, 'public')) {
            throw new RuntimeException(
                'The report image snapshot could not be stored.'
            );
        }

        return [
            $keyPrefix.'_url' => (
                $disk->url(
                    $destinationPath
                )
            ),
            $keyPrefix.'_path' => $destinationPath,
            $keyPrefix.'_mime_type' => (
                $media->mime_type
            ),
            $keyPrefix.'_hash' => hash(
                'sha256',
                $contents
            ),
        ];
    }

    private function assertSupportedMedia(
        TenantProfile $profile,
        Media $media,
        string $collection
    ): void {
        if ($media->disk !== self::REPORT_DISK) {
            throw new RuntimeException(
                'Report images must use the public disk.'
            );
        }

        if (
            ! in_array(
                $media->mime_type,
                self::ALLOWED_MIME_TYPES,
                true
            )
        ) {
            throw new RuntimeException(
                'The report image type is not supported.'
            );
        }

        if (
            ! in_array(
                strtolower($media->extension),
                self::ALLOWED_EXTENSIONS,
                true
            )
        ) {
            throw new RuntimeException(
                'The report image extension is not supported.'
            );
        }

        $expectedDirectory = sprintf(
            'tenant-profile/%s/%s',
            $profile->getKey(),
            $collection
        );

        if (
            $media->directory !== $expectedDirectory
            || $media->path !== (
                $media->directory.'/'.$media->filename
            )
        ) {
            throw new RuntimeException(
                'The report image storage path is invalid.'
            );
        }
    }

    /**
     * @return array<string, null>
     */
    private function emptySnapshot(
        string $keyPrefix
    ): array {
        return [
            $keyPrefix.'_url' => null,
            $keyPrefix.'_path' => null,
            $keyPrefix.'_mime_type' => null,
            $keyPrefix.'_hash' => null,
        ];
    }
}