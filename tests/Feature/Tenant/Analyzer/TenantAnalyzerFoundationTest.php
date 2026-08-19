<?php

namespace Tests\Feature\Tenant\Analyzer;

use App\Models\AnalyzerImportEvent;
use App\Models\AnalyzerInstrument;
use App\Models\AnalyzerMessage;
use App\Models\AnalyzerResultImport;
use App\Models\AnalyzerTestMapping;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\Support\Tenant\AnalyzerManagementTestCase;

class TenantAnalyzerFoundationTest extends AnalyzerManagementTestCase
{
    public function test_new_tenant_has_complete_analyzer_foundation_schema(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'analyzer-schema'
        );

        $this->runInTenant($context['tenant'], function (): void {
            foreach ([
                'analyzer_instruments',
                'analyzer_test_mappings',
                'analyzer_parameter_mappings',
                'analyzer_messages',
                'analyzer_result_imports',
                'analyzer_import_events',
            ] as $table) {
                $this->assertTrue(
                    Schema::connection('tenant')->hasTable($table),
                    'Missing analyzer table: '.$table
                );
            }
        });
    }

    public function test_administrator_creates_analyzer_and_receives_one_time_machine_token(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'analyzer-create'
        );
        $branch = $this->headOfficeBranch($context['tenant']);

        $response = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/analyzers', [
                'branch_uuid' => $branch->uuid,
                'code' => 'HEMA-01',
                'name' => 'Haematology Analyzer 01',
                'manufacturer' => 'Vendor',
                'model' => 'Model A',
                'transport_mode' => 'gateway',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.code', 'HEMA-01')
            ->assertJsonPath('data.branch.uuid', $branch->uuid);

        $token = $response->json('machine_token');

        $this->assertIsString($token);
        $this->assertStringStartsWith('rza_', $token);

        $this->runInTenant(
            $context['tenant'],
            function () use ($token): void {
                $instrument = AnalyzerInstrument::query()
                    ->where('code', 'HEMA-01')
                    ->firstOrFail();

                $this->assertTrue(
                    Hash::check($token, $instrument->machine_token_hash)
                );
                $this->assertStringNotContainsString(
                    $token,
                    (string) $instrument->machine_token_hash
                );
            }
        );
    }

    public function test_analyzer_source_message_and_import_event_evidence_is_protected(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'analyzer-evidence'
        );
        $branch = $this->headOfficeBranch($context['tenant']);

        $this->runInTenant(
            $context['tenant'],
            function () use ($branch): void {
                $instrument = AnalyzerInstrument::query()->create([
                    'branch_id' => $branch->getKey(),
                    'code' => 'IMMUTABLE-01',
                    'name' => 'Immutable Evidence Analyzer',
                    'adapter_key' => 'canonical_json_v1',
                    'transport_mode' => 'gateway',
                    'is_active' => true,
                ]);

                $message = AnalyzerMessage::query()->create([
                    'analyzer_instrument_id' => $instrument->getKey(),
                    'idempotency_key' => 'message-1',
                    'external_message_id' => 'device-1',
                    'adapter_key' => 'canonical_json_v1',
                    'content_type' => 'application/json',
                    'payload_hash' => str_repeat('a', 64),
                    'raw_payload' => '{"sample":"ABC"}',
                    'received_at' => now(),
                    'status' => AnalyzerMessage::STATUS_RECEIVED,
                ]);

                $import = AnalyzerResultImport::query()->create([
                    'analyzer_message_id' => $message->getKey(),
                    'analyzer_instrument_id' => $instrument->getKey(),
                    'source_result_key' => 'ABC:CBC',
                    'source_sample_identifier' => 'ABC',
                    'instrument_test_code' => 'CBC',
                    'status' => AnalyzerResultImport::STATUS_RECEIVED,
                ]);

                $event = AnalyzerImportEvent::query()->create([
                    'analyzer_result_import_id' => $import->getKey(),
                    'event_type' => 'received',
                    'actor_type' => 'system',
                    'payload' => [],
                ]);

                try {
                    $message->update(['raw_payload' => '{"changed":true}']);
                    $this->fail('Raw analyzer evidence must be immutable.');
                } catch (LogicException) {
                    $this->assertTrue(true);
                }

                try {
                    $event->update(['payload' => ['changed' => true]]);
                    $this->fail('Analyzer import events must be append-only.');
                } catch (LogicException) {
                    $this->assertTrue(true);
                }
            }
        );
    }
}
