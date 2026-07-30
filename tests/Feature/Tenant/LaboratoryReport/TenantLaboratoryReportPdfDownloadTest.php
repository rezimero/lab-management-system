<?php

namespace Tests\Feature\Tenant\LaboratoryReport;

use App\Models\LaboratoryReport;
use App\Services\Tenant\LaboratoryReport\LaboratoryReportPdfFilename;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Tenant\LaboratoryReportTestCase;

class TenantLaboratoryReportPdfDownloadTest extends
    LaboratoryReportTestCase
{
    public function test_permitted_user_downloads_a_pdf_response(): void
    {
        Storage::fake('public');

        $context = $this->authenticatedReportContext(
            'report-pdf-download'
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
        $reportNumber = $generated->json('data.report_number');

        $this->assertIsString($reportUuid);
        $this->assertIsString($reportNumber);

        $response = $this
            ->withHeaders($context['headers'])
            ->get(
                '/api/laboratory-reports/'
                .$reportUuid
                .'/pdf'
            );

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );

        $content = $response->getContent();

        $this->assertIsString($content);
        $this->assertStringStartsWith('%PDF-', $content);

        $disposition = $response
            ->headers
            ->get('content-disposition');

        $this->assertIsString($disposition);
        $this->assertStringContainsString(
            'attachment;',
            $disposition
        );
        $this->assertStringContainsString(
            $reportNumber.'.pdf',
            $disposition
        );
    }

    public function test_pdf_filename_is_sanitised(): void
    {
        $report = new LaboratoryReport([
            'report_number' => (
                'RPT / Unsafe: Name ? 100'
            ),
        ]);

        $this->assertSame(
            'RPT-Unsafe-Name-100.pdf',
            app(
                LaboratoryReportPdfFilename::class
            )->make($report)
        );
    }
}