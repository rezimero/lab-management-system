<?php

namespace Tests\Feature\Tenant\Analyzer;

use App\Models\AnalyzerMessage;
use App\Models\AnalyzerResultImport;
use App\Models\TestResult;
use Illuminate\Support\Facades\DB;
use Tests\Support\Tenant\AnalyzerManagementTestCase;

class TenantAnalyzerMachineIngestionTest extends AnalyzerManagementTestCase
{
    public function test_machine_authentication_requires_valid_analyzer_identity_and_token(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'machine-auth'
        );

        $target = $this->configuredAnalyzerResultTarget(
            $context,
            'machine-auth'
        );

        $payload = $this->canonicalAnalyzerPayload(
            $target['specimen']->accession_number
        );

        $headers = [
            ...$target['machine_headers'],
            'X-Rezhel-Idempotency-Key' => 'auth-1',
        ];

        $this->withHeaders([
            ...$headers,
            'Authorization' => 'Bearer wrong-token',
        ])->postJson(
            '/api/analyzer-machine/messages',
            $payload
        )->assertUnauthorized()
            ->assertJsonPath(
                'error_code',
                'ANALYZER_AUTH_FAILED'
            );

        $this->withHeaders([
            ...$headers,
            'X-Analyzer-Id' => '',
        ])->postJson(
            '/api/analyzer-machine/messages',
            $payload
        )->assertUnauthorized()
            ->assertJsonPath(
                'error_code',
                'ANALYZER_AUTH_FAILED'
            );
    }

    public function test_valid_canonical_message_creates_one_analyzer_origin_draft_and_preserves_encrypted_evidence(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'machine-import'
        );

        $target = $this->configuredAnalyzerResultTarget(
            $context,
            'machine-import'
        );

        $payload = $this->canonicalAnalyzerPayload(
            $target['specimen']->accession_number
        );

        $response = $this->withHeaders([
            ...$target['machine_headers'],
            'X-Rezhel-Idempotency-Key' => 'machine-import-1',
        ])->postJson(
            '/api/analyzer-machine/messages',
            $payload
        );

        $response
            ->assertAccepted()
            ->assertJsonPath('duplicate', false)
            ->assertJsonPath('data.status', AnalyzerMessage::STATUS_PROCESSED)
            ->assertJsonPath(
                'data.imports.0.status',
                AnalyzerResultImport::STATUS_IMPORTED
            );

        $result = $this->runInTenant(
            $context['tenant'],
            function () use ($target): TestResult {
                $result = TestResult::query()
                    ->with([
                        'originatingAnalyzerImport.instrument',
                        'originatingAnalyzerImport.message',
                    ])
                    ->where(
                        'laboratory_order_item_id',
                        $target['item']->getKey()
                    )
                    ->firstOrFail();

                $this->assertSame(
                    TestResult::STATUS_DRAFT,
                    $result->status
                );
                $this->assertNull($result->entered_by);
                $this->assertNotNull(
                    $result->originatingAnalyzerImport
                );

                return $result;
            }
        );

        $this->withHeaders($context['headers'])
            ->getJson('/api/test-results/'.$result->uuid)
            ->assertOk()
            ->assertJsonPath(
                'data.analyzer_source.instrument.uuid',
                $target['analyzer_uuid']
            )
            ->assertJsonPath(
                'data.analyzer_source.sample_identifier',
                $target['specimen']->accession_number
            );

        $this->runInTenant(
            $context['tenant'],
            function () use ($payload): void {
                $message = AnalyzerMessage::query()
                    ->firstOrFail();

                $storedCiphertext = DB::connection('tenant')
                    ->table('analyzer_messages')
                    ->where('id', $message->getKey())
                    ->value('raw_payload');

                $this->assertIsString($storedCiphertext);
                $this->assertNotSame(
                    json_encode($payload),
                    $storedCiphertext
                );
                $this->assertStringNotContainsString(
                    '"sample_id"',
                    $storedCiphertext
                );
            }
        );
    }

    public function test_idempotent_retry_does_not_create_duplicate_message_import_or_result(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'machine-idempotency'
        );

        $target = $this->configuredAnalyzerResultTarget(
            $context,
            'machine-idempotency'
        );

        $payload = $this->canonicalAnalyzerPayload(
            $target['specimen']->accession_number
        );

        $headers = [
            ...$target['machine_headers'],
            'X-Rezhel-Idempotency-Key' => 'same-message',
        ];

        $this->withHeaders($headers)
            ->postJson(
                '/api/analyzer-machine/messages',
                $payload
            )
            ->assertAccepted();

        $this->withHeaders($headers)
            ->postJson(
                '/api/analyzer-machine/messages',
                $payload
            )
            ->assertOk()
            ->assertJsonPath('duplicate', true);

        $this->runInTenant(
            $context['tenant'],
            function (): void {
                $this->assertSame(
                    1,
                    AnalyzerMessage::query()->count()
                );
                $this->assertSame(
                    1,
                    AnalyzerResultImport::query()->count()
                );
                $this->assertSame(
                    1,
                    TestResult::query()
                        ->whereNotNull(
                            'laboratory_order_item_id'
                        )
                        ->count()
                );
            }
        );
    }

    public function test_reusing_idempotency_key_with_different_payload_is_rejected(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'machine-idempotency-conflict'
        );

        $target = $this->configuredAnalyzerResultTarget(
            $context,
            'machine-idempotency-conflict'
        );

        $payload = $this->canonicalAnalyzerPayload(
            $target['specimen']->accession_number
        );

        $headers = [
            ...$target['machine_headers'],
            'X-Rezhel-Idempotency-Key' => 'conflict-key',
        ];

        $this->withHeaders($headers)
            ->postJson(
                '/api/analyzer-machine/messages',
                $payload
            )
            ->assertAccepted();

        $changed = $payload;
        $changed['message_id'] = 'different-message';

        $this->withHeaders($headers)
            ->postJson(
                '/api/analyzer-machine/messages',
                $changed
            )
            ->assertStatus(409);
    }
}
