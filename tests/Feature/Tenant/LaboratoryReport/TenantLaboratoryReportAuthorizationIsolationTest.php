<?php

namespace Tests\Feature\Tenant\LaboratoryReport;

use Tests\Support\Tenant\LaboratoryReportTestCase;

class TenantLaboratoryReportAuthorizationIsolationTest extends
    LaboratoryReportTestCase
{
    public function test_report_role_permissions_are_enforced(): void
    {
        $context = $this->authenticatedReportContext(
            'report-roles'
        );

        $receptionistHeaders = $this
            ->headersForReportRole(
                $context['tenant'],
                $context['registration']['domain'],
                'receptionist'
            );

        $technicianHeaders = $this
            ->headersForReportRole(
                $context['tenant'],
                $context['registration']['domain'],
                'lab_technician'
            );

        $doctorHeaders = $this
            ->headersForReportRole(
                $context['tenant'],
                $context['registration']['domain'],
                'doctor'
            );

        $this->resetReportAuthentication();

        $order = $this->createReportReadyOrder(
            $context['tenant'],
            $context['headers']
        );

        $this->resetReportAuthentication();

        $this
            ->withHeaders($receptionistHeaders)
            ->getJson(
                '/api/laboratory-reports'
            )
            ->assertOk();

        $this->resetReportAuthentication();

        $this
            ->generateReport(
                $receptionistHeaders,
                $order
            )
            ->assertForbidden();

        $this->resetReportAuthentication();

        $this
            ->generateReport(
                $technicianHeaders,
                $order
            )
            ->assertCreated();

        /*
         * The previous request authenticated the technician.
         * Reset the cached guard before using administrator
         * headers to prepare the second completed order.
         */
        $this->resetReportAuthentication();

        $doctorOrder = $this->createReportReadyOrder(
            $context['tenant'],
            $context['headers']
        );

        $this->resetReportAuthentication();

        $this
            ->generateReport(
                $doctorHeaders,
                $doctorOrder
            )
            ->assertCreated();
    }

    public function test_report_uuid_is_tenant_isolated(): void
    {
        $first = $this->authenticatedReportContext(
            'report-isolation-a'
        );

        $order = $this->createReportReadyOrder(
            $first['tenant'],
            $first['headers']
        );

        $response = $this
            ->generateReport(
                $first['headers'],
                $order
            )
            ->assertCreated();

        $reportUuid = $response->json(
            'data.uuid'
        );

        $this->assertIsString(
            $reportUuid
        );

        $second = $this->authenticatedReportContext(
            'report-isolation-b'
        );

        $this->resetReportAuthentication();

        $this
            ->withHeaders($second['headers'])
            ->getJson(
                '/api/laboratory-reports/'
                .$reportUuid
            )
            ->assertNotFound();
    }
}
