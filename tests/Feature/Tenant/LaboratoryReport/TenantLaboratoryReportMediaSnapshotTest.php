<?php

namespace Tests\Feature\Tenant\LaboratoryReport;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Tenant\LaboratoryReportTestCase;

class TenantLaboratoryReportMediaSnapshotTest extends
    LaboratoryReportTestCase
{
    public function test_report_copies_profile_media_into_immutable_storage(): void
    {
        Storage::fake('public');

        $context = $this->authenticatedReportContext(
            'report-media-snapshot'
        );

        $profile = $this->configureReportProfile(
            $context['tenant']
        );

        $profile->replaceMedia(
            $this->fakePng(
                'laboratory-logo.png'
            ),
            'logo'
        );

        $profile->replaceMedia(
            $this->fakePng(
                'director-signature.png'
            ),
            'director_signature'
        );

        $sourceLogo = $profile->getFirstMedia(
            'logo'
        );

        $sourceSignature = $profile->getFirstMedia(
            'director_signature'
        );

        $this->assertNotNull($sourceLogo);
        $this->assertNotNull($sourceSignature);

        $order = $this->createReportReadyOrder(
            $context['tenant'],
            $context['headers']
        );

        $response = $this
            ->generateReport(
                $context['headers'],
                $order
            )
            ->assertCreated();

        $reportUuid = $response->json(
            'data.uuid'
        );

        $this->assertIsString(
            $reportUuid
        );

        $report = $this->storedReport(
            $context['tenant'],
            $reportUuid
        );

        $snapshot = $report
            ->laboratory_snapshot;

        $logoPath = $snapshot[
            'logo_path'
        ] ?? null;

        $signaturePath = $snapshot[
            'director_signature_path'
        ] ?? null;

        $this->assertIsString($logoPath);
        $this->assertIsString($signaturePath);

        $this->assertStringStartsWith(
            'laboratory-reports/'
            .$reportUuid
            .'/',
            $logoPath
        );

        $this->assertStringStartsWith(
            'laboratory-reports/'
            .$reportUuid
            .'/',
            $signaturePath
        );

        $this->assertNotSame(
            $sourceLogo->path,
            $logoPath
        );

        $this->assertNotSame(
            $sourceSignature->path,
            $signaturePath
        );

        Storage::disk('public')
            ->assertExists($logoPath);

        Storage::disk('public')
            ->assertExists($signaturePath);

        $this->assertSame(
            hash(
                'sha256',
                Storage::disk('public')
                    ->get($logoPath)
            ),
            $snapshot['logo_hash']
        );

        $this->assertSame(
            hash(
                'sha256',
                Storage::disk('public')
                    ->get($signaturePath)
            ),
            $snapshot[
                'director_signature_hash'
            ]
        );

        $profile->clearMediaCollection(
            'logo'
        );

        $profile->clearMediaCollection(
            'director_signature'
        );

        Storage::disk('public')
            ->assertMissing($sourceLogo->path);

        Storage::disk('public')
            ->assertMissing(
                $sourceSignature->path
            );

        Storage::disk('public')
            ->assertExists($logoPath);

        Storage::disk('public')
            ->assertExists($signaturePath);
    }

    public function test_report_snapshots_null_media_when_profile_has_none(): void
    {
        Storage::fake('public');

        $context = $this->authenticatedReportContext(
            'report-media-empty'
        );

        $this->configureReportProfile(
            $context['tenant']
        );

        $order = $this->createReportReadyOrder(
            $context['tenant'],
            $context['headers']
        );

        $response = $this
            ->generateReport(
                $context['headers'],
                $order
            )
            ->assertCreated();

        $reportUuid = $response->json(
            'data.uuid'
        );

        $this->assertIsString(
            $reportUuid
        );

        $report = $this->storedReport(
            $context['tenant'],
            $reportUuid
        );

        $snapshot = $report
            ->laboratory_snapshot;

        $this->assertNull($snapshot['logo_path']);
        $this->assertNull($snapshot['logo_mime_type']);
        $this->assertNull($snapshot['logo_hash']);

        $this->assertNull(
            $snapshot[
                'director_signature_path'
            ]
        );

        $this->assertNull(
            $snapshot[
                'director_signature_mime_type'
            ]
        );

        $this->assertNull(
            $snapshot[
                'director_signature_hash'
            ]
        );
    }

    private function fakePng(
        string $name
    ): UploadedFile {
        $content = base64_decode(
            (
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAAB'
                .'CAQAAAC1HAwCAAAAC0lEQVR42mNk+A8A'
                .'AQUBAScY42YAAAAASUVORK5CYII='
            ),
            true
        );

        $this->assertIsString(
            $content
        );

        return UploadedFile::fake()
            ->createWithContent(
                $name,
                $content
            );
    }
}