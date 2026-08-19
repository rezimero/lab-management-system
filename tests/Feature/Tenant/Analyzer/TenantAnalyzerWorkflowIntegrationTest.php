<?php

namespace Tests\Feature\Tenant\Analyzer;

use App\Models\CriticalResultEscalation;
use App\Models\TestResult;
use Tests\Support\Tenant\AnalyzerManagementTestCase;

class TenantAnalyzerWorkflowIntegrationTest extends AnalyzerManagementTestCase
{
    public function test_analyzer_origin_draft_uses_existing_submit_approve_and_critical_escalation_workflow(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'analyzer-critical'
        );

        $target = $this->configuredAnalyzerResultTarget(
            $context,
            'analyzer-critical'
        );

        $payload = $this->canonicalAnalyzerPayload(
            $target['specimen']->accession_number
        );
        $payload['results'][0]['observations'][0]['flag'] = 'C';

        $this->withHeaders([
            ...$target['machine_headers'],
            'X-Rezhel-Idempotency-Key' => 'critical-machine-result',
        ])->postJson(
            '/api/analyzer-machine/messages',
            $payload
        )->assertAccepted();

        $result = $this->runInTenant(
            $context['tenant'],
            fn (): TestResult => TestResult::query()
                ->where(
                    'laboratory_order_item_id',
                    $target['item']->getKey()
                )
                ->firstOrFail()
        );

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'.$result->uuid.'/submit'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                TestResult::STATUS_SUBMITTED
            );

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'.$result->uuid.'/approve'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                TestResult::STATUS_APPROVED
            );

        $this->runInTenant(
            $context['tenant'],
            function () use ($result): void {
                $this->assertTrue(
                    CriticalResultEscalation::query()
                        ->where(
                            'test_result_uuid',
                            $result->uuid
                        )
                        ->exists()
                );
            }
        );
    }
}
