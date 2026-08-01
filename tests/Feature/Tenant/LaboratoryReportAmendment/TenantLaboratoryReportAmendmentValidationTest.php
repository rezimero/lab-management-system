<?php

namespace Tests\Feature\Tenant\LaboratoryReportAmendment;

use Tests\Support\Tenant\LaboratoryReportAmendmentTestCase;

class TenantLaboratoryReportAmendmentValidationTest extends LaboratoryReportAmendmentTestCase
{
    public function test_reason_and_actual_change_are_required(): void
    {
        $context = $this->authenticatedReportContext(
            'report-amendment-validation'
        );

        $source = $this->issueAmendableReport(
            $context['tenant'],
            $context['headers']
        );

        $this
            ->submitReportAmendment(
                $context['headers'],
                $source['report_uuid'],
                [
                    'reason' => 'short',
                    'patient' => [
                        'first_name' => 'Result',
                    ],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('reason');

        $this
            ->submitReportAmendment(
                $context['headers'],
                $source['report_uuid'],
                [
                    'reason' => (
                        'The patient name was checked against the source record.'
                    ),
                    'patient' => [
                        'first_name' => 'Result',
                    ],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amendment');

        $this
            ->submitReportAmendment(
                $context['headers'],
                $source['report_uuid'],
                [
                    'reason' => (
                        'The submitted result rows are identical to the effective result.'
                    ),
                    'results' => [
                        [
                            'result_uuid' => (
                                $source['result_uuid']
                            ),
                            'results_data' => (
                                $source['results_data']
                            ),
                        ],
                    ],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('results.0');
    }

    public function test_snapshot_corrections_require_objects(): void
    {
        $context = $this->authenticatedReportContext(
            'report-amendment-object-validation'
        );

        $source = $this->issueAmendableReport(
            $context['tenant'],
            $context['headers']
        );

        $this
            ->submitReportAmendment(
                $context['headers'],
                $source['report_uuid'],
                [
                    'reason' => (
                        'The malformed patient correction must be rejected safely.'
                    ),
                    'patient' => 'not-an-object',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('patient');

        $this
            ->submitReportAmendment(
                $context['headers'],
                $source['report_uuid'],
                [
                    'reason' => (
                        'The malformed order correction must be rejected safely.'
                    ),
                    'order' => 'not-an-object',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order');
    }

    public function test_only_one_open_request_and_only_current_report_can_be_amended(): void
    {
        $context = $this->authenticatedReportContext(
            'report-amendment-open'
        );

        $source = $this->issueAmendableReport(
            $context['tenant'],
            $context['headers']
        );

        $payload = [
            'reason' => (
                'The clinical notes require a verified clarification.'
            ),
            'order' => [
                'clinical_notes' => (
                    'Verified amended clinical note.'
                ),
            ],
        ];

        $first = $this
            ->submitReportAmendment(
                $context['headers'],
                $source['report_uuid'],
                $payload
            )
            ->assertCreated();

        $this
            ->submitReportAmendment(
                $context['headers'],
                $source['report_uuid'],
                $payload
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'laboratory_report'
            );

        $reviewerHeaders = $this->headersForReportRole(
            $context['tenant'],
            $context['registration']['domain'],
            'doctor'
        );

        $this->resetReportAuthentication();

        $approval = $this
            ->approveReportAmendment(
                $reviewerHeaders,
                $first->json('data.uuid')
            )
            ->assertOk();

        $this->resetReportAuthentication();

        $this
            ->submitReportAmendment(
                $context['headers'],
                $source['report_uuid'],
                [
                    'reason' => (
                        'A later source correction cannot target a superseded report.'
                    ),
                    'order' => [
                        'priority' => 'urgent',
                    ],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'laboratory_report'
            );

        $this->assertSame(
            2,
            $approval->json(
                'data.target_report.version'
            )
        );
    }

    public function test_unknown_snapshot_and_result_parameter_keys_are_rejected(): void
    {
        $context = $this->authenticatedReportContext(
            'report-amendment-strict'
        );

        $source = $this->issueAmendableReport(
            $context['tenant'],
            $context['headers']
        );

        $this
            ->submitReportAmendment(
                $context['headers'],
                $source['report_uuid'],
                [
                    'reason' => (
                        'The correction payload contains unsupported report fields.'
                    ),
                    'patient' => [
                        'id' => 999,
                    ],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('patient');

        $correctedResults = $source['results_data'];
        $correctedResults[0]['name'] = 'Unknown Parameter';

        $this
            ->submitReportAmendment(
                $context['headers'],
                $source['report_uuid'],
                [
                    'reason' => (
                        'The correction payload contains an invalid parameter name.'
                    ),
                    'results' => [
                        [
                            'result_uuid' => (
                                $source['result_uuid']
                            ),
                            'results_data' => (
                                $correctedResults
                            ),
                        ],
                    ],
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'results.0.results_data.0.name'
            );
    }
}
