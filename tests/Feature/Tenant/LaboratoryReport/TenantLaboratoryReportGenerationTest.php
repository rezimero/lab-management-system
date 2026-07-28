<?php

namespace Tests\Feature\Tenant\LaboratoryReport;

use App\Models\Patient;
use App\Models\TenantProfile;
use LogicException;
use Tests\Support\Tenant\LaboratoryReportTestCase;

class TenantLaboratoryReportGenerationTest extends
    LaboratoryReportTestCase
{
    public function test_completed_order_generates_snapshot_report(): void
    {
        $context = $this->authenticatedReportContext(
            'report-generation'
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
            ->assertCreated()
            ->assertJsonPath(
                'data.report_number',
                'RPT-'.$order->order_number
            )
            ->assertJsonPath(
                'data.laboratory.lab_name',
                'Clinical Diagnostics Laboratory'
            )
            ->assertJsonPath(
                'data.patient.full_name',
                'Result Patient'
            )
            ->assertJsonPath(
                'data.order.uuid',
                $order->uuid
            )
            ->assertJsonCount(
                2,
                'data.results'
            );

        $reportUuid = $response->json(
            'data.uuid'
        );

        $this->assertIsString(
            $reportUuid
        );

        $stored = $this->storedReport(
            $context['tenant'],
            $reportUuid
        );

        $this->assertSame(
            $order->id,
            $stored->laboratory_order_id
        );

        $this->assertSame(
            'completed',
            $stored->order_snapshot['status']
        );
    }

    public function test_report_snapshot_does_not_change_with_sources(): void
    {
        $context = $this->authenticatedReportContext(
            'report-snapshot'
        );

        $this->configureReportProfile(
            $context['tenant'],
            [
                'lab_name' => 'Original Laboratory',
            ]
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

        TenantProfile::query()
            ->where(
                'tenant_id',
                $context['tenant']->id
            )
            ->update([
                'lab_name' => 'Changed Laboratory',
            ]);

        $this->runInTenant(
            $context['tenant'],
            static function () use ($order): void {
                Patient::query()
                    ->whereKey($order->patient_id)
                    ->update([
                        'first_name' => 'Changed',
                    ]);
            }
        );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-reports/'
                .$reportUuid
            )
            ->assertOk()
            ->assertJsonPath(
                'data.laboratory.lab_name',
                'Original Laboratory'
            )
            ->assertJsonPath(
                'data.patient.first_name',
                'Result'
            );
    }


    public function test_signatory_snapshot_remains_unchanged(): void
    {
        $context = $this->authenticatedReportContext(
            'report-signatory-snapshot'
        );

        $this->configureReportProfile(
            $context['tenant'],
            [
                'director_name' => 'Dr Original Director',
                'director_qualification' => 'MBBS, FMCPath',
                'director_registration_number' => 'MDCN-1001',
                'signatory_designation' => (
                    'Consultant Pathologist'
                ),
                'accreditation_details' => (
                    'ISO 15189 accredited laboratory.'
                ),
            ]
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
            ->assertCreated()
            ->assertJsonPath(
                'data.laboratory.director_qualification',
                'MBBS, FMCPath'
            )
            ->assertJsonPath(
                'data.laboratory.director_registration_number',
                'MDCN-1001'
            )
            ->assertJsonPath(
                'data.laboratory.signatory_designation',
                'Consultant Pathologist'
            )
            ->assertJsonPath(
                'data.laboratory.accreditation_details',
                'ISO 15189 accredited laboratory.'
            );

        $reportUuid = $response->json('data.uuid');

        TenantProfile::query()
            ->where(
                'tenant_id',
                $context['tenant']->id
            )
            ->update([
                'director_name' => 'Dr Changed Director',
                'director_qualification' => 'Changed',
                'director_registration_number' => 'CHANGED',
                'signatory_designation' => 'Changed',
                'accreditation_details' => 'Changed',
            ]);

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-reports/'
                .$reportUuid
            )
            ->assertOk()
            ->assertJsonPath(
                'data.laboratory.director_name',
                'Dr Original Director'
            )
            ->assertJsonPath(
                'data.laboratory.director_qualification',
                'MBBS, FMCPath'
            )
            ->assertJsonPath(
                'data.laboratory.director_registration_number',
                'MDCN-1001'
            )
            ->assertJsonPath(
                'data.laboratory.signatory_designation',
                'Consultant Pathologist'
            )
            ->assertJsonPath(
                'data.laboratory.accreditation_details',
                'ISO 15189 accredited laboratory.'
            );
    }
    public function test_order_cannot_generate_duplicate_report(): void

    {
        $context = $this->authenticatedReportContext(
            'report-duplicate'
        );

        $order = $this->createReportReadyOrder(
            $context['tenant'],
            $context['headers']
        );

        $this
            ->generateReport(
                $context['headers'],
                $order
            )
            ->assertCreated();

        $this
            ->generateReport(
                $context['headers'],
                $order
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'laboratory_order'
            );
    }

    public function test_issued_report_model_is_immutable(): void
    {
        $context = $this->authenticatedReportContext(
            'report-immutable'
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

        $report = $this->storedReport(
            $context['tenant'],
            $response->json('data.uuid')
        );

        $this->expectException(
            LogicException::class
        );

        $report->update([
            'report_number' => 'CHANGED',
        ]);
    }
}
