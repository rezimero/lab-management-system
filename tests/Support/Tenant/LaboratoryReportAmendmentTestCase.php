<?php

namespace Tests\Support\Tenant;

use App\Models\LaboratoryOrder;
use App\Models\LaboratoryReportAmendment;
use App\Models\Tenant;
use Illuminate\Testing\TestResponse;

abstract class LaboratoryReportAmendmentTestCase extends LaboratoryReportTestCase
{
    /**
     * Submit an amendment through the public tenant API.
     *
     * @param  array<string, string>  $headers
     * @param  array<string, mixed>  $payload
     */
    protected function submitReportAmendment(
        array $headers,
        string $reportUuid,
        array $payload
    ): TestResponse {
        return $this
            ->withHeaders($headers)
            ->postJson(
                '/api/laboratory-reports/'
                .$reportUuid
                .'/amendments',
                $payload
            );
    }

    /**
     * Approve an amendment through the public tenant API.
     *
     * @param  array<string, string>  $headers
     */
    protected function approveReportAmendment(
        array $headers,
        string $amendmentUuid
    ): TestResponse {
        return $this
            ->withHeaders($headers)
            ->postJson(
                '/api/laboratory-report-amendments/'
                .$amendmentUuid
                .'/approve'
            );
    }

    /**
     * Reject an amendment through the public tenant API.
     *
     * @param  array<string, string>  $headers
     */
    protected function rejectReportAmendment(
        array $headers,
        string $amendmentUuid,
        string $reason = 'The correction requires additional clinical evidence.'
    ): TestResponse {
        return $this
            ->withHeaders($headers)
            ->postJson(
                '/api/laboratory-report-amendments/'
                .$amendmentUuid
                .'/reject',
                [
                    'rejection_reason' => $reason,
                ]
            );
    }

    /**
     * Issue a one-item source report.
     *
     * @param  array<string, string>  $headers
     * @return array{
     *     order: LaboratoryOrder,
     *     report_uuid: string,
     *     result_uuid: string,
     *     results_data: array<int, array<string, mixed>>
     * }
     */
    protected function issueAmendableReport(
        Tenant $tenant,
        array $headers
    ): array {
        $order = $this->createReportReadyOrder(
            $tenant,
            $headers,
            1
        );

        $response = $this
            ->generateReport($headers, $order)
            ->assertCreated();

        $reportUuid = $response->json('data.uuid');
        $resultUuid = $response->json(
            'data.results.0.result_uuid'
        );
        $resultsData = $response->json(
            'data.results.0.results_data'
        );

        $this->assertIsString($reportUuid);
        $this->assertIsString($resultUuid);
        $this->assertIsArray($resultsData);

        return [
            'order' => $order,
            'report_uuid' => $reportUuid,
            'result_uuid' => $resultUuid,
            'results_data' => $resultsData,
        ];
    }

    /**
     * Retrieve stored workflow evidence inside one tenant.
     */
    protected function storedReportAmendment(
        Tenant $tenant,
        string $uuid
    ): LaboratoryReportAmendment {
        return $this->runInTenant(
            $tenant,
            static fn (): LaboratoryReportAmendment => (
                LaboratoryReportAmendment::query()
                    ->with([
                        'sourceReport',
                        'targetReport',
                        'resultAmendments',
                    ])
                    ->where('uuid', $uuid)
                    ->firstOrFail()
            )
        );
    }
}
