<?php

namespace Tests\Feature\Tenant\LaboratoryReport;

use App\Events\Tenant\Portal\LaboratoryReportAvailableForClinician;
use App\Events\Tenant\Portal\LaboratoryReportAvailableForPatient;
use App\Models\ClinicianPortalAccount;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\ReferringClinician;
use App\Models\TenantProfile;
use Illuminate\Support\Facades\Event;
use LogicException;
use Tests\Support\Tenant\LaboratoryReportTestCase;

class TenantLaboratoryReportGenerationTest extends LaboratoryReportTestCase
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

    public function test_issued_report_emits_portal_availability_events(): void
    {
        Event::fake([
            LaboratoryReportAvailableForPatient::class,
            LaboratoryReportAvailableForClinician::class,
        ]);

        $context = $this->authenticatedReportContext(
            'report-portal-events'
        );
        $order = $this->createReportReadyOrder(
            $context['tenant'],
            $context['headers']
        );

        $portalAccounts = $this->runInTenant(
            $context['tenant'],
            function () use ($order): array {
                $patientAccount = PatientPortalAccount::query()
                    ->create([
                        'patient_id' => $order->patient_id,
                        'email' => 'report-patient@example.test',
                        'password' => 'PortalPassword123!',
                        'email_verified_at' => now(),
                        'activated_at' => now(),
                        'is_active' => true,
                    ]);

                $clinician = ReferringClinician::query()
                    ->create([
                        'name' => 'Dr Report Portal',
                        'email' => 'report-clinician@example.test',
                        'is_active' => true,
                    ]);

                $clinicianAccount = ClinicianPortalAccount::query()
                    ->create([
                        'referring_clinician_id' => (
                            $clinician->getKey()
                        ),
                        'email' => 'report-clinician@example.test',
                        'password' => 'PortalPassword123!',
                        'email_verified_at' => now(),
                        'activated_at' => now(),
                        'is_active' => true,
                    ]);

                $order->update([
                    'referring_clinician_id' => (
                        $clinician->getKey()
                    ),
                    'referring_clinician' => $clinician->name,
                ]);

                return [
                    'patient' => $patientAccount,
                    'clinician' => $clinicianAccount,
                ];
            }
        );

        $response = $this->generateReport(
            $context['headers'],
            $order
        )->assertCreated();

        $reportUuid = (string) $response->json('data.uuid');

        Event::assertDispatched(
            LaboratoryReportAvailableForPatient::class,
            fn (LaboratoryReportAvailableForPatient $event): bool => (
                $event->tenantId === $context['tenant']->id
                && $event->reportUuid === $reportUuid
                && $event->accountUuid
                    === $portalAccounts['patient']->uuid
            )
        );

        Event::assertDispatched(
            LaboratoryReportAvailableForClinician::class,
            fn (LaboratoryReportAvailableForClinician $event): bool => (
                $event->tenantId === $context['tenant']->id
                && $event->reportUuid === $reportUuid
                && $event->accountUuid
                    === $portalAccounts['clinician']->uuid
            )
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
