<?php

namespace Tests\Feature\Tenant\LaboratoryReport;

use App\Models\LaboratoryReport;
use App\Services\Tenant\LaboratoryReport\LaboratoryReportPdfService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Tenant\LaboratoryReportTestCase;

class TenantLaboratoryReportPdfImageSecurityTest extends
    LaboratoryReportTestCase
{
    public function test_pdf_embeds_only_report_owned_local_images(): void
    {
        [$report, $service] = $this->reportWithImages(
            'report-pdf-images'
        );

        $html = $service->renderHtml($report);

        $this->assertSame(
            2,
            substr_count(
                $html,
                'data:image/png;base64,'
            )
        );

        $laboratory = $report->laboratory_snapshot;
        $laboratory['logo_url'] = (
            'https://attacker.example/logo.png'
        );
        $laboratory['logo_path'] = null;
        $laboratory['logo_mime_type'] = null;
        $laboratory['logo_hash'] = null;

        $report->setAttribute(
            'laboratory_snapshot',
            $laboratory
        );

        $html = $service->renderHtml($report);

        $this->assertStringNotContainsString(
            'attacker.example',
            $html
        );
        $this->assertSame(
            1,
            substr_count(
                $html,
                'data:image/png;base64,'
            )
        );
    }

    public function test_pdf_rejects_traversal_hash_and_mime_mismatch(): void
    {
        foreach (
            [
                'traversal' => static function (
                    LaboratoryReport $report,
                    array $laboratory
                ): array {
                    $laboratory['logo_path'] = (
                        'laboratory-reports/'
                        .$report->uuid
                        .'/../logo.png'
                    );

                    return $laboratory;
                },
                'hash' => static function (
                    LaboratoryReport $report,
                    array $laboratory
                ): array {
                    $laboratory['logo_hash'] = str_repeat(
                        '0',
                        64
                    );

                    return $laboratory;
                },
                'mime' => static function (
                    LaboratoryReport $report,
                    array $laboratory
                ): array {
                    $laboratory['logo_mime_type'] = (
                        'image/jpeg'
                    );

                    return $laboratory;
                },
            ] as $case => $mutate
        ) {
            [$report, $service] = $this->reportWithImages(
                'report-pdf-'.$case
            );

            $report->setAttribute(
                'laboratory_snapshot',
                $mutate(
                    $report,
                    $report->laboratory_snapshot
                )
            );

            $html = $service->renderHtml($report);

            $this->assertSame(
                1,
                substr_count(
                    $html,
                    'data:image/png;base64,'
                ),
                $case
            );
        }
    }

    /**
     * @return array{0: LaboratoryReport, 1: LaboratoryReportPdfService}
     */
    private function reportWithImages(
        string $prefix
    ): array {
        Storage::fake('public');

        $context = $this->authenticatedReportContext(
            $prefix
        );

        $profile = $this->configureReportProfile(
            $context['tenant']
        );

        $profile->replaceMedia(
            $this->fakePng('logo.png'),
            'logo'
        );
        $profile->replaceMedia(
            $this->fakePng('signature.png'),
            'director_signature'
        );

        $order = $this->createReportReadyOrder(
            $context['tenant'],
            $context['headers']
        );

        $generated = $this
            ->generateReport(
                $context['headers'],
                $order
            )
            ->assertCreated();

        $reportUuid = $generated->json('data.uuid');
        $this->assertIsString($reportUuid);

        return [
            $this->storedReport(
                $context['tenant'],
                $reportUuid
            ),
            app(
                LaboratoryReportPdfService::class
            ),
        ];
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

        $this->assertIsString($content);

        return UploadedFile::fake()
            ->createWithContent(
                $name,
                $content
            );
    }
}