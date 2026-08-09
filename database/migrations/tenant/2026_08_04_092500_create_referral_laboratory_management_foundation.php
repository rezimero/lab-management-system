<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create(
            'referral_laboratories',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('code', 60)->unique();
                $table->string('name', 180);
                $table->string('accreditation_number', 120)->nullable();
                $table->string('contact_name')->nullable();
                $table->string('email')->nullable();
                $table->string('phone', 40)->nullable();
                $table->string('address_line_1')->nullable();
                $table->string('address_line_2')->nullable();
                $table->string('city', 120)->nullable();
                $table->string('state', 120)->nullable();
                $table->string('country', 120)->default('Nigeria');
                $table->char('currency', 3)->default('NGN');
                $table->unsignedInteger('default_turnaround_hours')->nullable();
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $this->actorColumns($table);
                $table->timestampsTz();
                $table->softDeletesTz();

                $table->index(['is_active', 'name'], 'ref_labs_active_name_idx');
            }
        );

        Schema::connection('tenant')->create(
            'referral_laboratory_tests',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('referral_laboratory_id')
                    ->constrained('referral_laboratories')
                    ->restrictOnDelete();
                $table->foreignId('lab_test_id')
                    ->constrained('lab_tests')
                    ->restrictOnDelete();
                $table->string('external_test_code', 120);
                $table->string('external_test_name', 180);
                $table->decimal('cost', 14, 2)->default(0);
                $table->char('currency', 3)->default('NGN');
                $table->unsignedInteger('turnaround_hours')->nullable();
                $table->json('specimen_requirements')->nullable();
                $table->boolean('is_active')->default(true);
                $this->actorColumns($table);
                $table->timestampsTz();
                $table->softDeletesTz();

                $table->unique(
                    ['referral_laboratory_id', 'lab_test_id'],
                    'ref_lab_tests_lab_test_unique'
                );
                $table->index(
                    ['referral_laboratory_id', 'is_active'],
                    'ref_lab_tests_lab_active_idx'
                );
                $table->index(
                    ['lab_test_id', 'is_active'],
                    'ref_lab_tests_test_active_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'laboratory_referrals',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->string('referral_number', 80)->unique();
                $table->foreignId('laboratory_order_item_id')
                    ->constrained('laboratory_order_items')
                    ->restrictOnDelete();
                $table->foreignId('referral_laboratory_id')
                    ->constrained('referral_laboratories')
                    ->restrictOnDelete();
                $table->foreignId('referral_laboratory_test_id')
                    ->constrained('referral_laboratory_tests')
                    ->restrictOnDelete();
                $table->foreignId('dispatch_branch_id')
                    ->constrained('branches')
                    ->restrictOnDelete();
                $table->uuid('open_order_item_key')->nullable();
                $table->string('status', 32)->default('draft');
                $table->json('laboratory_snapshot');
                $table->json('test_snapshot');
                $table->decimal('quoted_cost', 14, 2)->default(0);
                $table->decimal('final_cost', 14, 2)->nullable();
                $table->char('currency', 3)->default('NGN');
                $table->string('external_reference', 160)->nullable();
                $table->text('dispatch_notes')->nullable();
                $table->timestampTz('turnaround_due_at')->nullable();

                $this->workflowActorColumn($table, 'created_by');
                $this->workflowActorColumn($table, 'dispatched_by');
                $this->workflowActorColumn($table, 'acknowledged_by');
                $this->workflowActorColumn($table, 'result_received_by');
                $this->workflowActorColumn($table, 'completed_by');
                $this->workflowActorColumn($table, 'rejected_by');
                $this->workflowActorColumn($table, 'cancelled_by');

                $table->timestampsTz();
                $table->timestampTz('dispatched_at')->nullable();
                $table->timestampTz('acknowledged_at')->nullable();
                $table->timestampTz('result_received_at')->nullable();
                $table->timestampTz('completed_at')->nullable();
                $table->timestampTz('rejected_at')->nullable();
                $table->timestampTz('cancelled_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->text('cancellation_reason')->nullable();

                $table->unique(
                    'open_order_item_key',
                    'lab_referrals_open_item_unique'
                );
                $table->index(
                    ['laboratory_order_item_id', 'status'],
                    'lab_referrals_item_status_idx'
                );
                $table->index(
                    ['referral_laboratory_id', 'status'],
                    'lab_referrals_lab_status_idx'
                );
                $table->index(
                    ['dispatch_branch_id', 'status'],
                    'lab_referrals_branch_status_idx'
                );
                $table->index(
                    ['status', 'turnaround_due_at'],
                    'lab_referrals_status_due_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'laboratory_referral_specimens',
            function (Blueprint $table): void {
                $table->id();
                $table->foreignId('laboratory_referral_id')
                    ->constrained('laboratory_referrals')
                    ->restrictOnDelete();
                $table->foreignId('specimen_id')
                    ->constrained('specimens')
                    ->restrictOnDelete();
                $table->json('specimen_snapshot');
                $table->foreignId('linked_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestampsTz();

                $table->unique(
                    ['laboratory_referral_id', 'specimen_id'],
                    'lab_referral_specimens_unique'
                );
                $table->index(
                    ['specimen_id', 'laboratory_referral_id'],
                    'lab_referral_specimens_specimen_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'laboratory_referral_events',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('laboratory_referral_id')
                    ->constrained('laboratory_referrals')
                    ->restrictOnDelete();
                $table->string('event_type', 40);
                $table->string('from_status', 32)->nullable();
                $table->string('to_status', 32)->nullable();
                $table->foreignId('actor_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->json('actor_snapshot');
                $table->json('payload')->nullable();
                $table->timestampTz('occurred_at');
                $table->timestampTz('created_at')->useCurrent();

                $table->index(
                    ['laboratory_referral_id', 'occurred_at'],
                    'lab_referral_events_timeline_idx'
                );
                $table->index(
                    ['event_type', 'occurred_at'],
                    'lab_referral_events_type_idx'
                );
            }
        );

        Schema::connection('tenant')->create(
            'laboratory_referral_results',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('laboratory_referral_id')
                    ->constrained('laboratory_referrals')
                    ->restrictOnDelete();
                $table->unsignedInteger('version');
                $table->foreignId('supersedes_referral_result_id')
                    ->nullable();
                $table->string('external_report_number', 160)->nullable();
                $table->json('results_data');
                $table->text('notes')->nullable();
                $table->timestampTz('reported_at')->nullable();
                $table->timestampTz('received_at');
                $table->foreignId('received_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->json('receiver_snapshot');
                $table->char('source_payload_hash', 64);
                $table->foreignId('test_result_id')
                    ->constrained('test_results')
                    ->restrictOnDelete();
                $table->timestampTz('created_at')->useCurrent();

                $table->unique(
                    ['laboratory_referral_id', 'version'],
                    'lab_referral_results_version_unique'
                );
                $table->unique(
                    'supersedes_referral_result_id',
                    'lab_referral_results_predecessor_unique'
                );
                $table->index(
                    ['test_result_id', 'created_at'],
                    'lab_referral_results_test_result_idx'
                );

                $table->foreign(
                    'supersedes_referral_result_id',
                    'lab_referral_results_predecessor_foreign'
                )
                    ->references('id')
                    ->on('laboratory_referral_results')
                    ->restrictOnDelete();
            }
        );
    }

    public function down(): void
    {
        if (
            Schema::connection('tenant')->hasTable('laboratory_referrals')
            && DB::connection('tenant')
                ->table('laboratory_referrals')
                ->exists()
        ) {
            throw new RuntimeException(
                'Refusing to remove referral-laboratory workflow history.'
            );
        }

        Schema::connection('tenant')->dropIfExists('laboratory_referral_results');
        Schema::connection('tenant')->dropIfExists('laboratory_referral_events');
        Schema::connection('tenant')->dropIfExists('laboratory_referral_specimens');
        Schema::connection('tenant')->dropIfExists('laboratory_referrals');
        Schema::connection('tenant')->dropIfExists('referral_laboratory_tests');
        Schema::connection('tenant')->dropIfExists('referral_laboratories');
    }

    private function actorColumns(Blueprint $table): void
    {
        $this->workflowActorColumn($table, 'created_by');
        $this->workflowActorColumn($table, 'updated_by');
    }

    private function workflowActorColumn(
        Blueprint $table,
        string $column
    ): void {
        $table->foreignId($column)
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();
    }
};
