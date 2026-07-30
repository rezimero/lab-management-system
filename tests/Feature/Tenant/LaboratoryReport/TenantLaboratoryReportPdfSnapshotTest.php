<?php

namespace Tests\Feature\Tenant\LaboratoryReport;

use App\Services\Tenant\LaboratoryReport\LaboratoryReportPdfService;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Tenant\LaboratoryReportTestCase;

class TenantLaboratoryReportPdfSnapshotTest extends
    LaboratoryReportTestCase
{
    public function test_pdf_uses_issued_snapshot_after_profile_changes(): void
    {
        Storage::fake('public');

        $context = $this->authenticatedReportContext(
            'report-pdf-snapshot'
        );

        $profile = $this->configureReportProfile(
            $context['tenant'],
            [
                'lab_name' => (
                    'Original Snapshot Laboratory'
                ),
                'report_footer' => (
                    'Original immutable report footer.'
                ),
            ]
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

        $profile->forceFill([
            'lab_name' => 'Changed Current Laboratory',
            'report_footer' => (
                'Changed current report footer.'
            ),
        ])->save();

        $report = $this->storedReport(
            $context['tenant'],
            $reportUuid
        );

        $html = app(
            LaboratoryReportPdfService::class
        )->renderHtml($report);

        $this->assertStringContainsString(
            'Original Snapshot Laboratory',
            $html
        );
        $this->assertStringContainsString(
            'Original immutable report footer.',
            $html
        );
        $this->assertStringNotContainsString(
            'Changed Current Laboratory',
            $html
        );
        $this->assertStringNotContainsString(
            'Changed current report footer.',
            $html
        );
    }

    public function test_pdf_generates_without_logo_or_signature(): void
    {
        Storage::fake('public');

        $context = $this->authenticatedReportContext(
            'report-pdf-no-media'
        );

        $this->configureReportProfile(
            $context['tenant']
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

        $this
            ->withHeaders($context['headers'])
            ->get(
                '/api/laboratory-reports/'
                .$reportUuid
                .'/pdf'
            )
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );
    }
}