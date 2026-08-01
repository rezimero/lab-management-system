<?php

namespace Tests\Feature\Tenant\LaboratoryReportAmendment;

use App\Models\TestResult;
use LogicException;
use Tests\Support\Tenant\LaboratoryReportAmendmentTestCase;

class TenantLaboratoryReportAmendmentWorkflowTest extends LaboratoryReportAmendmentTestCase
{
    public function test_submitted_amendment_creates_new_immutable_report_version(): void
    {
        $context = $this->authenticatedReportContext(
            'report-amendment-approval'
        );

        $source = $this->issueAmendableReport(
            $context['tenant'],
            $context['headers']
        );

        $correctedResults = $source['results_data'];
        $correctedResults[0]['value'] = '14.2';
        $correctedResults[0]['flag'] = 'normal';

        $submission = $this
            ->submitReportAmendment(
                $context['headers'],
                $source['report_uuid'],
                [
                    'reason' => (
                        'The patient identity and haemoglobin value were transcribed incorrectly.'
                    ),
                    'patient' => [
                        'first_name' => 'Corrected',
                    ],
                    'results' => [
                        [
                            'result_uuid' => (
                                $source['result_uuid']
                            ),
                            'results_data' => (
                                $correctedResults
                            ),
                            'notes' => (
                                'Corrected after source verification.'
                            ),
                        ],
                    ],
                ]
            )
            ->assertCreated()
            ->assertJsonPath('data.status', 'submitted')
            ->assertJsonPath(
                'data.source_report.uuid',
                $source['report_uuid']
            );

        $amendmentUuid = $submission->json(
            'data.uuid'
        );

        $this->assertIsString($amendmentUuid);

        $this->resetReportAuthentication();

        $this
            ->approveReportAmendment(
                $context['headers'],
                $amendmentUuid
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'authorization'
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
                $amendmentUuid
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath(
                'data.target_report.version',
                2
            )
            ->assertJsonPath(
                'data.result_amendments.0.test_result_uuid',
                $source['result_uuid']
            );

        $targetUuid = $approval->json(
            'data.target_report.uuid'
        );

        $this->assertIsString($targetUuid);

        $this->resetReportAuthentication();

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-orders/'
                .$source['order']->uuid
                .'/report'
            )
            ->assertOk()
            ->assertJsonPath('data.uuid', $targetUuid)
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath(
                'data.lifecycle_status',
                'amended'
            )
            ->assertJsonPath(
                'data.patient.first_name',
                'Corrected'
            )
            ->assertJsonPath(
                'data.results.0.results_data.0.value',
                '14.2'
            )
            ->assertJsonPath(
                'data.results.0.effective_version',
                2
            );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-reports/'
                .$source['report_uuid']
            )
            ->assertOk()
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath(
                'data.lifecycle_status',
                'superseded'
            )
            ->assertJsonPath(
                'data.patient.first_name',
                'Result'
            )
            ->assertJsonPath(
                'data.results.0.results_data.0.value',
                '13.5'
            )
            ->assertJsonPath(
                'data.superseded_by.uuid',
                $targetUuid
            );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/test-results/'
                .$source['result_uuid']
            )
            ->assertOk()
            ->assertJsonPath(
                'data.effective_version',
                2
            )
            ->assertJsonPath('data.is_amended', true)
            ->assertJsonPath(
                'data.results_data.0.value',
                '14.2'
            );

        $originalResult = $this->runInTenant(
            $context['tenant'],
            static fn (): TestResult => (
                TestResult::query()
                    ->where(
                        'uuid',
                        $source['result_uuid']
                    )
                    ->firstOrFail()
            )
        );

        $this->assertSame(
            '13.5',
            $originalResult->results_data[0]['value']
        );

        $stored = $this->storedReportAmendment(
            $context['tenant'],
            $amendmentUuid
        );

        $this->assertCount(
            1,
            $stored->resultAmendments
        );
        $resultAmendment = $stored
            ->resultAmendments
            ->first();

        $this->assertNotNull($resultAmendment);
        $this->assertSame(2, $resultAmendment->version);
        $this->assertSame(
            $stored->requested_by,
            $resultAmendment->amended_by
        );
        $this->assertSame(
            $stored->reviewed_by,
            $resultAmendment->approved_by
        );
        $this->assertSame(
            $stored->requester_snapshot,
            $resultAmendment->amendment_actor_snapshot
        );
        $this->assertSame(
            $stored->reviewer_snapshot,
            $resultAmendment->approval_actor_snapshot
        );

        $this->expectException(LogicException::class);

        $stored->targetReport->update([
            'report_number' => 'CHANGED',
        ]);
    }

    public function test_result_amendments_are_sequential_and_can_clear_notes(): void
    {
        $context = $this->authenticatedReportContext(
            'report-amendment-sequential-results'
        );

        $source = $this->issueAmendableReport(
            $context['tenant'],
            $context['headers']
        );

        $firstResults = $source['results_data'];
        $firstResults[0]['value'] = '14.0';

        $firstSubmission = $this
            ->submitReportAmendment(
                $context['headers'],
                $source['report_uuid'],
                [
                    'reason' => (
                        'The first corrected value and note clearance were verified.'
                    ),
                    'results' => [
                        [
                            'result_uuid' => (
                                $source['result_uuid']
                            ),
                            'results_data' => $firstResults,
                            'notes' => null,
                        ],
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

        $firstApproval = $this
            ->approveReportAmendment(
                $reviewerHeaders,
                $firstSubmission->json('data.uuid')
            )
            ->assertOk()
            ->assertJsonPath('data.target_report.version', 2);

        $firstTargetUuid = $firstApproval->json(
            'data.target_report.uuid'
        );

        $this->assertIsString($firstTargetUuid);

        $this->resetReportAuthentication();

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/test-results/'
                .$source['result_uuid']
            )
            ->assertOk()
            ->assertJsonPath('data.effective_version', 2)
            ->assertJsonPath('data.notes', null);

        $secondResults = $firstResults;
        $secondResults[0]['value'] = '14.4';

        $secondSubmission = $this
            ->submitReportAmendment(
                $context['headers'],
                $firstTargetUuid,
                [
                    'reason' => (
                        'A second verified source document changed the effective value.'
                    ),
                    'results' => [
                        [
                            'result_uuid' => (
                                $source['result_uuid']
                            ),
                            'results_data' => $secondResults,
                            'notes' => (
                                'Second correction verified against the analyzer record.'
                            ),
                        ],
                    ],
                ]
            )
            ->assertCreated();

        $this->resetReportAuthentication();

        $secondApproval = $this
            ->approveReportAmendment(
                $reviewerHeaders,
                $secondSubmission->json('data.uuid')
            )
            ->assertOk()
            ->assertJsonPath('data.target_report.version', 3)
            ->assertJsonPath(
                'data.target_report.report_number',
                'RPT-'.$source['order']->order_number.'-A2'
            );

        $secondTargetUuid = $secondApproval->json(
            'data.target_report.uuid'
        );

        $this->assertIsString($secondTargetUuid);

        $this->resetReportAuthentication();

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/test-results/'
                .$source['result_uuid']
            )
            ->assertOk()
            ->assertJsonPath('data.effective_version', 3)
            ->assertJsonPath(
                'data.results_data.0.value',
                '14.4'
            )
            ->assertJsonPath(
                'data.notes',
                'Second correction verified against the analyzer record.'
            );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-reports/'
                .$firstTargetUuid
            )
            ->assertOk()
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.results.0.notes', null)
            ->assertJsonPath(
                'data.lifecycle_status',
                'superseded'
            );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-reports/'
                .$secondTargetUuid
            )
            ->assertOk()
            ->assertJsonPath('data.version', 3)
            ->assertJsonPath(
                'data.results.0.effective_version',
                3
            );

        $versions = $this->runInTenant(
            $context['tenant'],
            static function () use ($source): array {
                $result = TestResult::query()
                    ->with([
                        'amendments' => static fn ($query) => (
                            $query->orderBy('version')
                        ),
                    ])
                    ->where('uuid', $source['result_uuid'])
                    ->firstOrFail();

                return $result->amendments
                    ->map(
                        static fn ($amendment): array => [
                            'id' => $amendment->id,
                            'version' => $amendment->version,
                            'supersedes_id' => (
                                $amendment
                                    ->supersedes_test_result_amendment_id
                            ),
                            'source_hash' => (
                                $amendment->source_payload_hash
                            ),
                        ]
                    )
                    ->all();
            }
        );

        $this->assertCount(2, $versions);
        $this->assertSame(2, $versions[0]['version']);
        $this->assertNull($versions[0]['supersedes_id']);
        $this->assertSame(3, $versions[1]['version']);
        $this->assertSame(
            $versions[0]['id'],
            $versions[1]['supersedes_id']
        );
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $versions[0]['source_hash']
        );
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $versions[1]['source_hash']
        );
    }

    public function test_rejected_amendment_is_terminal_and_releases_open_request(): void
    {
        $context = $this->authenticatedReportContext(
            'report-amendment-rejection'
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
                        'The referring clinician name requires correction.'
                    ),
                    'order' => [
                        'referring_clinician' => (
                            'Dr Correct Clinician'
                        ),
                    ],
                ]
            )
            ->assertCreated();

        $amendmentUuid = $submission->json('data.uuid');

        $reviewerHeaders = $this->headersForReportRole(
            $context['tenant'],
            $context['registration']['domain'],
            'doctor'
        );

        $this->resetReportAuthentication();

        $this
            ->rejectReportAmendment(
                $reviewerHeaders,
                $amendmentUuid
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.is_open', false);

        $this->resetReportAuthentication();

        $this
            ->approveReportAmendment(
                $reviewerHeaders,
                $amendmentUuid
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amendment');

        $this->resetReportAuthentication();

        $this
            ->submitReportAmendment(
                $context['headers'],
                $source['report_uuid'],
                [
                    'reason' => (
                        'The referring clinician name is now verified and corrected.'
                    ),
                    'order' => [
                        'referring_clinician' => (
                            'Dr Correct Clinician'
                        ),
                    ],
                ]
            )
            ->assertCreated();
    }
}
