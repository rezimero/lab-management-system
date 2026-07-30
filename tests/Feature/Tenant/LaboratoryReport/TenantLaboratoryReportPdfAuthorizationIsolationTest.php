<?php

namespace Tests\Feature\Tenant\LaboratoryReport;

use App\Http\Requests\LaboratoryReport\ViewLaboratoryReportRequest;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Tenant\LaboratoryReportTestCase;

class TenantLaboratoryReportPdfAuthorizationIsolationTest extends
    LaboratoryReportTestCase
{
    public function test_request_denies_an_unknown_role(): void
    {
        $request = new ViewLaboratoryReportRequest();

        $request->setUserResolver(
            static fn (): TenantUser => (
                new TenantUser([
                    'role' => 'billing_officer',
                ])
            )
        );

        $this->assertFalse(
            $request->authorize()
        );
    }

    public function test_pdf_report_uuid_is_tenant_isolated(): void
    {
        Storage::fake('public');

        $first = $this->authenticatedReportContext(
            'report-pdf-isolation-a'
        );

        $this->configureReportProfile(
            $first['tenant']
        );

        $order = $this->createReportReadyOrder(
            $first['tenant'],
            $first['headers']
        );

        $generated = $this
            ->generateReport(
                $first['headers'],
                $order
            )
            ->assertCreated();

        $reportUuid = $generated->json('data.uuid');
        $this->assertIsString($reportUuid);

        $second = $this->authenticatedReportContext(
            'report-pdf-isolation-b'
        );

        $this->resetReportAuthentication();

        $this
            ->withHeaders($second['headers'])
            ->get(
                '/api/laboratory-reports/'
                .$reportUuid
                .'/pdf'
            )
            ->assertNotFound();
    }
}