<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create(
            'analyzer_instruments',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('branch_id')
                    ->constrained('branches')
                    ->restrictOnDelete();
                $table->string('code', 60)->unique();
                $table->string('name', 180);
                $table->string('manufacturer', 180)->nullable();
                $table->string('model', 180)->nullable();
                $table->string('serial_number', 180)->nullable();
                $table->string('adapter_key', 80)
                    ->default('canonical_json_v1');
                $table->string('transport_mode', 40)
                    ->default('gateway');
                $table->string('machine_token_hash')->nullable();
                $table->string('machine_token_hint', 16)->nullable();
                $table->timestampTz('token_rotated_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestampTz('last_seen_at')->nullable();
                $table->timestampTz('last_success_at')->nullable();
                $table->timestampTz('last_error_at')->nullable();
                $table->string('last_error_code', 80)->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampsTz();

                $table->index(
                    ['branch_id', 'is_active'],
                    'analyzer_instrument_branch_active_idx'
                );
                $table->index(
                    ['manufacturer', 'model'],
                    'analyzer_instrument_make_model_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'analyzer_test_mappings',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('analyzer_instrument_id')
                    ->constrained('analyzer_instruments')
                    ->restrictOnDelete();
                $table->foreignId('lab_test_id')
                    ->constrained('lab_tests')
                    ->restrictOnDelete();
                $table->string('instrument_test_code', 120);
                $table->string('instrument_test_name', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampsTz();

                $table->unique(
                    [
                        'analyzer_instrument_id',
                        'instrument_test_code',
                    ],
                    'analyzer_test_mapping_instrument_code_unique'
                );
                $table->index(
                    ['lab_test_id', 'is_active'],
                    'analyzer_test_mapping_lab_test_active_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'analyzer_parameter_mappings',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('analyzer_test_mapping_id')
                    ->constrained('analyzer_test_mappings')
                    ->restrictOnDelete();
                $table->string('instrument_parameter_code', 120);
                $table->string('instrument_parameter_name', 255)
                    ->nullable();
                $table->string('target_parameter_name', 255);
                $table->string('expected_unit', 120)->nullable();
                $table->boolean('is_required')->default(true);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->foreignId('updated_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampsTz();

                $table->unique(
                    [
                        'analyzer_test_mapping_id',
                        'instrument_parameter_code',
                    ],
                    'analyzer_parameter_mapping_code_unique'
                );
                $table->unique(
                    [
                        'analyzer_test_mapping_id',
                        'target_parameter_name',
                    ],
                    'analyzer_parameter_mapping_target_unique'
                );
            }
        );

        Schema::connection('tenant')->create(
            'analyzer_messages',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('analyzer_instrument_id')
                    ->constrained('analyzer_instruments')
                    ->restrictOnDelete();
                $table->string('idempotency_key', 191);
                $table->string('external_message_id', 191)->nullable();
                $table->string('adapter_key', 80);
                $table->string('content_type', 120)
                    ->default('application/json');
                $table->char('payload_hash', 64);
                $table->longText('raw_payload');
                $table->timestampTz('source_timestamp')->nullable();
                $table->timestampTz('received_at');
                $table->timestampTz('processed_at')->nullable();
                $table->string('status', 30)->default('received');
                $table->string('last_error_code', 80)->nullable();
                $table->text('last_error')->nullable();
                $table->timestampsTz();

                $table->unique(
                    ['analyzer_instrument_id', 'idempotency_key'],
                    'analyzer_message_idempotency_unique'
                );
                $table->index(
                    ['analyzer_instrument_id', 'received_at'],
                    'analyzer_message_instrument_received_idx'
                );
                $table->index(
                    ['status', 'received_at'],
                    'analyzer_message_status_received_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'analyzer_result_imports',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('analyzer_message_id')
                    ->constrained('analyzer_messages')
                    ->restrictOnDelete();
                $table->foreignId('analyzer_instrument_id')
                    ->constrained('analyzer_instruments')
                    ->restrictOnDelete();
                $table->foreignId('analyzer_test_mapping_id')
                    ->nullable()
                    ->constrained('analyzer_test_mappings')
                    ->restrictOnDelete();
                $table->foreignId('specimen_id')
                    ->nullable()
                    ->constrained('specimens')
                    ->restrictOnDelete();
                $table->foreignId('laboratory_order_item_id')
                    ->nullable()
                    ->constrained('laboratory_order_items')
                    ->restrictOnDelete();
                $table->foreignId('test_result_id')
                    ->nullable()
                    ->constrained('test_results')
                    ->restrictOnDelete();
                $table->string('source_result_key', 191);
                $table->string('source_sample_identifier', 191);
                $table->string('instrument_test_code', 120);
                $table->string('status', 30)->default('received');
                $table->json('normalized_results')->nullable();
                $table->json('mapping_snapshot')->nullable();
                $table->json('validation_issues')->nullable();
                $table->unsignedSmallInteger('attempts')->default(0);
                $table->timestampTz('last_processed_at')->nullable();
                $table->foreignId('accepted_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampTz('accepted_at')->nullable();
                $table->foreignId('rejected_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampTz('rejected_at')->nullable();
                $table->text('review_note')->nullable();
                $table->timestampsTz();

                $table->unique(
                    ['analyzer_message_id', 'source_result_key'],
                    'analyzer_result_import_source_unique'
                );
                $table->index(
                    ['status', 'created_at'],
                    'analyzer_result_import_status_created_idx'
                );
                $table->index(
                    ['source_sample_identifier', 'created_at'],
                    'analyzer_result_import_sample_created_idx'
                );
                $table->index(
                    ['laboratory_order_item_id', 'created_at'],
                    'analyzer_result_import_order_item_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'analyzer_import_events',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('analyzer_result_import_id')
                    ->constrained('analyzer_result_imports')
                    ->restrictOnDelete();
                $table->string('event_type', 80);
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30)->nullable();
                $table->string('actor_type', 30);
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->json('actor_snapshot')->nullable();
                $table->json('payload')->nullable();
                $table->timestampTz('occurred_at');
                $table->timestampTz('created_at');

                $table->index(
                    ['analyzer_result_import_id', 'occurred_at'],
                    'analyzer_import_event_import_occurred_idx'
                );
                $table->index(
                    ['event_type', 'occurred_at'],
                    'analyzer_import_event_type_occurred_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::connection('tenant')
            ->dropIfExists('analyzer_import_events');
        Schema::connection('tenant')
            ->dropIfExists('analyzer_result_imports');
        Schema::connection('tenant')
            ->dropIfExists('analyzer_messages');
        Schema::connection('tenant')
            ->dropIfExists('analyzer_parameter_mappings');
        Schema::connection('tenant')
            ->dropIfExists('analyzer_test_mappings');
        Schema::connection('tenant')
            ->dropIfExists('analyzer_instruments');
    }
};
