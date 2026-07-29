<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Models\LaboratoryReport;
use finfo;
use Illuminate\Support\Facades\Storage;

class LaboratoryReportPdfImageResolver
{
    private const REPORT_DISK = 'public';

    /**
     * @var array<int, string>
     */
    private const ALLOWED_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * @param array<string, mixed> $laboratory
     */
    public function resolve(
        LaboratoryReport $report,
        array $laboratory,
        string $prefix
    ): ?string {
        $path = $laboratory[
            $prefix.'_path'
        ] ?? null;

        $mimeType = $laboratory[
            $prefix.'_mime_type'
        ] ?? null;

        $expectedHash = $laboratory[
            $prefix.'_hash'
        ] ?? null;

        if (
            ! is_string($path)
            || ! is_string($mimeType)
            || ! in_array(
                $mimeType,
                self::ALLOWED_IMAGE_MIME_TYPES,
                true
            )
        ) {
            return null;
        }

        if (! $this->isSafeReportPath(
            $report,
            $path
        )) {
            return null;
        }

        $disk = Storage::disk(
            self::REPORT_DISK
        );

        if (! $disk->exists($path)) {
            return null;
        }

        $contents = $disk->get($path);

        if (! is_string($contents) || $contents === '') {
            return null;
        }

        $actualMimeType = (
            new finfo(FILEINFO_MIME_TYPE)
        )->buffer($contents);

        if (
            ! is_string($actualMimeType)
            || $actualMimeType !== $mimeType
            || ! in_array(
                $actualMimeType,
                self::ALLOWED_IMAGE_MIME_TYPES,
                true
            )
        ) {
            return null;
        }

        if (! $this->hashMatches(
            $expectedHash,
            $contents
        )) {
            return null;
        }

        return sprintf(
            'data:%s;base64,%s',
            $actualMimeType,
            base64_encode($contents)
        );
    }

    private function isSafeReportPath(
        LaboratoryReport $report,
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
            'laboratory-reports/'
            .$report->uuid
            .'/'
        );
    }

    private function hashMatches(
        mixed $expectedHash,
        string $contents
    ): bool {
        if (
            ! is_string($expectedHash)
            || $expectedHash === ''
        ) {
            return true;
        }

        return hash_equals(
            $expectedHash,
            hash('sha256', $contents)
        );
    }
}