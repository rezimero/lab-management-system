<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Models\LaboratoryReport;
use App\Models\Media;
use App\Models\TenantProfile;
use finfo;
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
     * Copy tenant-profile media into immutable report-owned storage.
     *
     * @return array<string, string|null>
     */
    public function snapshot(
        TenantProfile $profile,
        string $reportUuid
    ): array {
        $this->assertReportUuid($reportUuid);

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
     * Copy immutable report-owned media to an amended report UUID.
     *
     * @return array<string, string|null>
     */
    public function copyFromReport(
        LaboratoryReport $sourceReport,
        string $targetReportUuid
    ): array {
        $this->assertReportUuid($targetReportUuid);

        $snapshot = is_array(
            $sourceReport->laboratory_snapshot
        )
            ? $sourceReport->laboratory_snapshot
            : [];

        return array_merge(
            $this->copyReportImage(
                $sourceReport,
                $snapshot,
                'logo',
                $targetReportUuid
            ),
            $this->copyReportImage(
                $sourceReport,
                $snapshot,
                'director_signature',
                $targetReportUuid
            )
        );
    }

    /**
     * Remove copied media after failed report generation or approval.
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

        return $this->storeReportImage(
            $contents,
            $media->mime_type,
            strtolower($media->extension),
            $keyPrefix,
            $reportUuid
        );
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<string, string|null>
     */
    private function copyReportImage(
        LaboratoryReport $sourceReport,
        array $snapshot,
        string $keyPrefix,
        string $targetReportUuid
    ): array {
        $sourcePath = $snapshot[
            $keyPrefix.'_path'
        ] ?? null;
        $mimeType = $snapshot[
            $keyPrefix.'_mime_type'
        ] ?? null;
        $expectedHash = $snapshot[
            $keyPrefix.'_hash'
        ] ?? null;

        if ($sourcePath === null) {
            return $this->emptySnapshot($keyPrefix);
        }

        if (
            ! is_string($sourcePath)
            || ! is_string($mimeType)
            || ! is_string($expectedHash)
            || preg_match(
                '/^[a-f0-9]{64}$/',
                $expectedHash
            ) !== 1
            || ! in_array(
                $mimeType,
                self::ALLOWED_MIME_TYPES,
                true
            )
            || ! $this->isSafeSourceReportPath(
                $sourceReport,
                $sourcePath
            )
        ) {
            throw new RuntimeException(
                'The source report image snapshot is invalid.'
            );
        }

        $extension = strtolower(
            pathinfo(
                $sourcePath,
                PATHINFO_EXTENSION
            )
        );

        if (
            ! in_array(
                $extension,
                self::ALLOWED_EXTENSIONS,
                true
            )
        ) {
            throw new RuntimeException(
                'The source report image extension is invalid.'
            );
        }

        $disk = Storage::disk(self::REPORT_DISK);

        if (! $disk->exists($sourcePath)) {
            throw new RuntimeException(
                'The immutable source report image is missing.'
            );
        }

        $contents = $disk->get($sourcePath);

        if (! is_string($contents) || $contents === '') {
            throw new RuntimeException(
                'The immutable source report image could not be read.'
            );
        }

        $actualMimeType = (
            new finfo(FILEINFO_MIME_TYPE)
        )->buffer($contents);

        if (
            ! is_string($actualMimeType)
            || $actualMimeType !== $mimeType
        ) {
            throw new RuntimeException(
                'The immutable source report image MIME type changed.'
            );
        }

        $actualHash = hash('sha256', $contents);

        if (
            ! hash_equals(
                $expectedHash,
                $actualHash
            )
        ) {
            throw new RuntimeException(
                'The immutable source report image hash changed.'
            );
        }

        return $this->storeReportImage(
            $contents,
            $mimeType,
            $extension,
            $keyPrefix,
            $targetReportUuid
        );
    }

    /**
     * @return array<string, string>
     */
    private function storeReportImage(
        string $contents,
        string $mimeType,
        string $extension,
        string $keyPrefix,
        string $reportUuid
    ): array {
        $destinationPath = sprintf(
            '%s/%s/%s.%s',
            self::REPORT_DIRECTORY,
            $reportUuid,
            str_replace('_', '-', $keyPrefix),
            $extension
        );

        $disk = Storage::disk(self::REPORT_DISK);

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
            $keyPrefix.'_url' => $disk->url(
                $destinationPath
            ),
            $keyPrefix.'_path' => $destinationPath,
            $keyPrefix.'_mime_type' => $mimeType,
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

    private function isSafeSourceReportPath(
        LaboratoryReport $sourceReport,
        string $path
    ): bool {
        if (
            str_contains($path, "\0")
            || str_contains($path, '\\')
            || str_contains($path, '..')
        ) {
            return false;
        }

        return str_starts_with(
            $path,
            self::REPORT_DIRECTORY
            .'/'.$sourceReport->uuid.'/'
        );
    }

    private function assertReportUuid(
        string $reportUuid
    ): void {
        if (! Str::isUuid($reportUuid)) {
            throw new RuntimeException(
                'The report media snapshot requires a valid UUID.'
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
