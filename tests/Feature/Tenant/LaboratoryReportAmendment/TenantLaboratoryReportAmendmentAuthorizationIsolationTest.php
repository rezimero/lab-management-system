<?php

namespace Tests\Feature\Tenant\LaboratoryReportAmendment;

use Tests\Support\Tenant\LaboratoryReportAmendmentTestCase;

class TenantLaboratoryReportAmendmentAuthorizationIsolationTest extends LaboratoryReportAmendmentTestCase
{
    public function test_requester_and_approver_role_matrix_is_enforced(): void
    {
        $context = $this->authenticatedReportContext(
            'report-amendment-roles'
        );

        $receptionistHeaders = $this->headersForReportRole(
            $context['tenant'],
            $context['registration']['domain'],
            'receptionist'
        );

        $technicianHeaders = $this->headersForReportRole(
            $context['tenant'],
            $context['registration']['domain'],
            'lab_technician'
        );

        $doctorHeaders = $this->headersForReportRole(
            $context['tenant'],
            $context['registration']['domain'],
            'doctor'
        );

        $this->resetReportAuthentication();

        $source = $this->issueAmendableReport(
            $context['tenant'],
            $context['headers']
        );

        $payload = [
            'reason' => (
                'The order priority was recorded incorrectly at issue time.'
            ),
            'order' => [
                'priority' => 'urgent',
            ],
        ];

        $this->resetReportAuthentication();

        $this
            ->submitReportAmendment(
                $receptionistHeaders,
                $source['report_uuid'],
                $payload
            )
            ->assertForbidden();

        $this->resetReportAuthentication();

        $submission = $this
            ->submitReportAmendment(
                $technicianHeaders,
                $source['report_uuid'],
                $payload
            )
            ->assertCreated();

        $this->resetReportAuthentication();

        $this
            ->approveReportAmendment(
                $technicianHeaders,
                $submission->json('data.uuid')
            )
            ->assertForbidden();

        $this->resetReportAuthentication();

        $this
            ->approveReportAmendment(
                $doctorHeaders,
                $submission->json('data.uuid')
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_amendment_uuid_is_tenant_isolated(): void
    {
        $first = $this->authenticatedReportContext(
            'report-amendment-isolation-a'
        );

        $source = $this->issueAmendableReport(
            $first['tenant'],
            $first['headers']
        );

        $submission = $this
            ->submitReportAmendment(
                $first['headers'],
                $source['report_uuid'],
                [
                    'reason' => (
                        'The clinical notes require a tenant-local correction.'
                    ),
                    'order' => [
                        'clinical_notes' => (
                            'Tenant A corrected note.'
                        ),
                    ],
                ]
            )
            ->assertCreated();

        $amendmentUuid = $submission->json('data.uuid');

        $second = $this->authenticatedReportContext(
            'report-amendment-isolation-b'
        );

        $this->resetReportAuthentication();

        $this
            ->withHeaders($second['headers'])
            ->getJson(
                '/api/laboratory-report-amendments/'
                .$amendmentUuid
            )
            ->assertNotFound();
    }
}
