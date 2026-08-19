<?php

namespace Tests\Feature\Tenant\Analyzer;

use App\Models\AnalyzerResultImport;
use App\Models\TestResult;
use Tests\Support\Tenant\AnalyzerManagementTestCase;

class TenantAnalyzerValidationQueueTest extends AnalyzerManagementTestCase
{
    public function test_unit_mismatch_enters_validation_queue_and_can_be_reprocessed_after_mapping_fix(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'validation-unit'
        );

        $target = $this->configuredAnalyzerResultTarget(
            $context,
            'validation-unit'
        );

        $payload = $this->canonicalAnalyzerPayload(
            $target['specimen']->accession_number
        );
        $payload['results'][0]['observations'][0]['unit'] = 'mg/dL';

        $this->withHeaders([
            ...$target['machine_headers'],
            'X-Rezhel-Idempotency-Key' => 'unit-mismatch',
        ])->postJson(
            '/api/analyzer-machine/messages',
            $payload
        )->assertAccepted()
            ->assertJsonPath(
                'data.imports.0.status',
                AnalyzerResultImport::STATUS_NEEDS_REVIEW
            );

        $import = $this->runInTenant(
            $context['tenant'],
            fn (): AnalyzerResultImport => (
                AnalyzerResultImport::query()
                    ->firstOrFail()
            )
        );

        $this->assertSame(
            'ANALYZER_UNIT_MISMATCH',
            $import->validation_issues[0]['code']
        );

        $this->runInTenant(
            $context['tenant'],
            fn () => $this->assertSame(
                0,
                TestResult::query()
                    ->where(
                        'laboratory_order_item_id',
                        $target['item']->getKey()
                    )
                    ->count()
            )
        );

        $this->withHeaders($context['headers'])
            ->putJson(
                '/api/analyzers/'
                    .$target['analyzer_uuid']
                    .'/test-mappings/'
                    .$target['test_mapping_uuid']
                    .'/parameter-mappings/'
                    .$target['parameter_mappings']['HGB'],
                [
                    'instrument_parameter_code' => 'HGB',
                    'target_parameter_name' => 'Haemoglobin',
                    'expected_unit' => 'mg/dL',
                    'is_required' => true,
                    'is_active' => true,
                ]
            )
            ->assertOk();

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/analyzer-imports/'
                    .$import->uuid
                    .'/reprocess'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                AnalyzerResultImport::STATUS_IMPORTED
            );

        $this->runInTenant(
            $context['tenant'],
            function () use ($target): void {
                $this->assertSame(
                    1,
                    TestResult::query()
                        ->where(
                            'laboratory_order_item_id',
                            $target['item']->getKey()
                        )
                        ->count()
                );
            }
        );
    }

    public function test_unmapped_test_code_enters_validation_queue_without_result_mutation(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'validation-unmapped'
        );

        $target = $this->configuredAnalyzerResultTarget(
            $context,
            'validation-unmapped'
        );

        $payload = $this->canonicalAnalyzerPayload(
            $target['specimen']->accession_number
        );
        $payload['results'][0]['test_code'] = 'UNMAPPED';

        $this->withHeaders([
            ...$target['machine_headers'],
            'X-Rezhel-Idempotency-Key' => 'unmapped-test',
        ])->postJson(
            '/api/analyzer-machine/messages',
            $payload
        )->assertAccepted()
            ->assertJsonPath(
                'data.imports.0.status',
                AnalyzerResultImport::STATUS_NEEDS_REVIEW
            )
            ->assertJsonPath(
                'data.imports.0.validation_issues.0.code',
                'ANALYZER_TEST_UNMAPPED'
            );

        $this->runInTenant(
            $context['tenant'],
            function () use ($target): void {
                $this->assertFalse(
                    TestResult::query()
                        ->where(
                            'laboratory_order_item_id',
                            $target['item']->getKey()
                        )
                        ->exists()
                );
            }
        );
    }

    public function test_existing_manual_result_is_never_overwritten_by_analyzer_import(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'validation-conflict'
        );

        $target = $this->configuredAnalyzerResultTarget(
            $context,
            'validation-conflict'
        );

        $this->createDraftResult(
            $context['headers'],
            $target['item']
        )->assertCreated();

        $payload = $this->canonicalAnalyzerPayload(
            $target['specimen']->accession_number
        );

        $this->withHeaders([
            ...$target['machine_headers'],
            'X-Rezhel-Idempotency-Key' => 'manual-conflict',
        ])->postJson(
            '/api/analyzer-machine/messages',
            $payload
        )->assertAccepted()
            ->assertJsonPath(
                'data.imports.0.status',
                AnalyzerResultImport::STATUS_NEEDS_REVIEW
            );

        $this->runInTenant(
            $context['tenant'],
            function () use ($target): void {
                $this->assertSame(
                    1,
                    TestResult::query()
                        ->where(
                            'laboratory_order_item_id',
                            $target['item']->getKey()
                        )
                        ->count()
                );

                $import = AnalyzerResultImport::query()
                    ->firstOrFail();

                $codes = collect(
                    $import->validation_issues ?? []
                )->pluck('code');

                $this->assertTrue(
                    $codes->contains(
                        'ANALYZER_RESULT_CONFLICT'
                    )
                );
            }
        );
    }

    public function test_reviewer_can_reject_validation_queue_item_with_append_only_event(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'validation-reject'
        );

        $target = $this->configuredAnalyzerResultTarget(
            $context,
            'validation-reject'
        );

        $payload = $this->canonicalAnalyzerPayload(
            $target['specimen']->accession_number
        );
        $payload['results'][0]['test_code'] = 'UNMAPPED';

        $this->withHeaders([
            ...$target['machine_headers'],
            'X-Rezhel-Idempotency-Key' => 'reject-me',
        ])->postJson(
            '/api/analyzer-machine/messages',
            $payload
        )->assertAccepted();

        $import = $this->runInTenant(
            $context['tenant'],
            fn (): AnalyzerResultImport => (
                AnalyzerResultImport::query()
                    ->firstOrFail()
            )
        );

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/analyzer-imports/'
                    .$import->uuid
                    .'/reject',
                [
                    'note' => 'Device result does not belong to this order.',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                AnalyzerResultImport::STATUS_REJECTED
            );

        $this->runInTenant(
            $context['tenant'],
            function () use ($import): void {
                $stored = AnalyzerResultImport::query()
                    ->with('events')
                    ->whereKey($import->getKey())
                    ->firstOrFail();

                $this->assertNotNull($stored->rejected_at);
                $this->assertTrue(
                    $stored->events
                        ->pluck('event_type')
                        ->contains('rejected')
                );
            }
        );
    }
}
