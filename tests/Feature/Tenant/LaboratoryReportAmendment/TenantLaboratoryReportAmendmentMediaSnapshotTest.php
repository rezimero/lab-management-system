<?php

namespace Tests\Feature\Tenant\LaboratoryReportAmendment;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Tenant\LaboratoryReportAmendmentTestCase;

class TenantLaboratoryReportAmendmentMediaSnapshotTest extends LaboratoryReportAmendmentTestCase
{
    public function test_amended_report_copies_source_report_media_not_live_profile_media(): void
    {
        Storage::fake('public');

        $context = $this->authenticatedReportContext(
            'report-amendment-media-copy'
        );

        $profile = $this->configureReportProfile(
            $context['tenant']
        );

        $profile->replaceMedia(
            $this->fakePng('source-logo.png', 'source'),
            'logo'
        );

        $source = $this->issueAmendableReport(
            $context['tenant'],
            $context['headers']
        );

        $sourceReport = $this->storedReport(
            $context['tenant'],
            $source['report_uuid']
        );

        $sourcePath = $sourceReport
            ->laboratory_snapshot['logo_path'];
        $sourceHash = $sourceReport
            ->laboratory_snapshot['logo_hash'];

        $this->assertIsString($sourcePath);
        $this->assertIsString($sourceHash);

        $sourceContents = Storage::disk('public')
            ->get($sourcePath);

        $profile->replaceMedia(
            $this->fakePng('live-logo.png', 'live'),
            'logo'
        );

        $liveMedia = $profile->getFirstMedia('logo');

        $this->assertNotNull($liveMedia);

        $liveContents = Storage::disk('public')
            ->get($liveMedia->path);

        $this->assertNotSame(
            hash('sha256', $sourceContents),
            hash('sha256', $liveContents)
        );

        $submission = $this
            ->submitReportAmendment(
                $context['headers'],
                $source['report_uuid'],
                [
                    'reason' => (
                        'The referring clinician name requires a verified correction.'
                    ),
                    'order' => [
                        'referring_clinician' => (
                            'Dr Immutable Media Reviewer'
                        ),
                    ],
                ]
            )
            ->assertCreated();

        $reviewerHeaders = $this->headersForReportRole(
            $context['tenant'],
            $context['registration']['domain'],
            'doctor'
        );

        $this->resetReportAuthentication();

        $approval = $this
            ->approveReportAmendment(
                $reviewerHeaders,
                $submission->json('data.uuid')
            )
            ->assertOk();

        $targetUuid = $approval->json(
            'data.target_report.uuid'
        );

        $this->assertIsString($targetUuid);

        $targetReport = $this->storedReport(
            $context['tenant'],
            $targetUuid
        );

        $targetPath = $targetReport
            ->laboratory_snapshot['logo_path'];
        $targetHash = $targetReport
            ->laboratory_snapshot['logo_hash'];

        $this->assertIsString($targetPath);
        $this->assertIsString($targetHash);
        $this->assertNotSame($sourcePath, $targetPath);
        $this->assertStringStartsWith(
            'laboratory-reports/'.$targetUuid.'/',
            $targetPath
        );
        $this->assertSame($sourceHash, $targetHash);
        $this->assertSame(
            $sourceContents,
            Storage::disk('public')->get($targetPath)
        );

        Storage::disk('public')->assertExists($sourcePath);
        Storage::disk('public')->assertExists($targetPath);
    }

    private function fakePng(
        string $name,
        string $variant
    ): UploadedFile {
        $encoded = $variant === 'source'
            ? (
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAf'
                .'FcSJAAAADUlEQVR4nGP4z8DwHwAFAAH/iZk9HQAA'
                .'AABJRU5ErkJggg=='
            )
            : (
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAf'
                .'FcSJAAAADUlEQVR4nGNgYPj/HwADAgH/5ncLrgAA'
                .'AABJRU5ErkJggg=='
            );

        $content = base64_decode($encoded, true);

        $this->assertIsString($content);

        return UploadedFile::fake()
            ->createWithContent($name, $content);
    }
}
