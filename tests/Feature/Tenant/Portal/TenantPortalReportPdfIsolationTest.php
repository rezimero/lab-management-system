<?php

namespace Tests\Feature\Tenant\Portal;

use Illuminate\Support\Facades\Storage;
use Tests\Support\Tenant\PortalReadApiTestCase;

class TenantPortalReportPdfIsolationTest extends PortalReadApiTestCase
{
    public function test_patient_pdf_uses_same_current_report_ownership_boundary(): void
    {
        Storage::fake('public');

        $tenant = $this->registerTestTenant(
            'patient-portal-pdf'
        );

        $patientA = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );

        $patientB = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );

        $ownOrder = $this->createPortalOrder(
            $tenant['tenant'],
            $patientA['patient']
        );

        $foreignOrder = $this->createPortalOrder(
            $tenant['tenant'],
            $patientB['patient']
        );

        $original = $this->createPortalReport(
            $tenant['tenant'],
            $ownOrder
        );

        $current = $this->createPortalReport(
            $tenant['tenant'],
            $ownOrder,
            2,
            $original
        );

        $foreign = $this->createPortalReport(
            $tenant['tenant'],
            $foreignOrder
        );

        $token = $this->loginPatientPortal(
            $tenant,
            $patientA
        );

        $headers = $this->portalHeaders(
            $token,
            $tenant['domain']
        );

        $pdf = $this->withHeaders($headers)
            ->get(
                '/api/portal/patient/reports/'
                .$current->uuid
                .'/pdf'
            )
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );

        $content = $pdf->getContent();

        $this->assertIsString($content);
        $this->assertStringStartsWith(
            '%PDF-',
            $content
        );

        $this->withHeaders($headers)
            ->get(
                '/api/portal/patient/reports/'
                .$original->uuid
                .'/pdf'
            )
            ->assertNotFound();

        $this->withHeaders($headers)
            ->get(
                '/api/portal/patient/reports/'
                .$foreign->uuid
                .'/pdf'
            )
            ->assertNotFound();
    }

    public function test_clinician_pdf_requires_explicit_linked_order_ownership(): void
    {
        Storage::fake('public');

        $tenant = $this->registerTestTenant(
            'clinician-portal-pdf'
        );

        $clinician = $this->createClinicianPortalIdentity(
            $tenant['tenant']
        );

        $patient = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );

        $linkedOrder = $this->createPortalOrder(
            $tenant['tenant'],
            $patient['patient'],
            $clinician['clinician']
        );

        $unlinkedOrder = $this->createPortalOrder(
            $tenant['tenant'],
            $patient['patient'],
            null,
            [
                'referring_clinician' => (
                    $clinician['clinician']->name
                ),
            ]
        );

        $linkedReport = $this->createPortalReport(
            $tenant['tenant'],
            $linkedOrder
        );

        $unlinkedReport = $this->createPortalReport(
            $tenant['tenant'],
            $unlinkedOrder
        );

        $token = $this->loginClinicianPortal(
            $tenant,
            $clinician
        );

        $headers = $this->portalHeaders(
            $token,
            $tenant['domain']
        );

        $this->withHeaders($headers)
            ->get(
                '/api/portal/clinician/reports/'
                .$linkedReport->uuid
                .'/pdf'
            )
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );

        $this->withHeaders($headers)
            ->get(
                '/api/portal/clinician/reports/'
                .$unlinkedReport->uuid
                .'/pdf'
            )
            ->assertNotFound();
    }
}
