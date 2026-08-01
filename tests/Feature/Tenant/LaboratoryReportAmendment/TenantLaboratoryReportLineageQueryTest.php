<?php

namespace Tests\Feature\Tenant\LaboratoryReportAmendment;

use Tests\Support\Tenant\LaboratoryReportAmendmentTestCase;

class TenantLaboratoryReportLineageQueryTest extends LaboratoryReportAmendmentTestCase
{
    public function test_current_default_historical_filter_and_lineage_contracts(): void
    {
        $context = $this->authenticatedReportContext(
            'report-lineage-query'
        );

        $source = $this->issueAmendableReport(
            $context['tenant'],
            $context['headers']
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
                            'Dr Verified Clinician'
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
        $amendmentUuid = $submission->json('data.uuid');

        $this->assertIsString($targetUuid);
        $this->assertIsString($amendmentUuid);

        $this->resetReportAuthentication();

        $this
            ->withHeaders($context['headers'])
            ->getJson('/api/laboratory-reports')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $targetUuid);

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-reports'
                .'?include_superseded=true'
            )
            ->assertOk()
            ->assertJsonPath('meta.total', 2);

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-reports/'
                .$source['report_uuid']
                .'/lineage'
            )
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.version', 1)
            ->assertJsonPath('data.1.version', 2)
            ->assertJsonPath('data.1.uuid', $targetUuid);

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-reports/'
                .$source['report_uuid']
                .'/amendments'
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $amendmentUuid)
            ->assertJsonPath('data.0.status', 'approved');

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-report-amendments/'
                .$amendmentUuid
            )
            ->assertOk()
            ->assertJsonPath('data.source_report.uuid', $source['report_uuid'])
            ->assertJsonPath('data.target_report.uuid', $targetUuid);
    }
}
