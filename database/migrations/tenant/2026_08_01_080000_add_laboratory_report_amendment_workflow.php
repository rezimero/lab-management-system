<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add immutable report lineage and amendment workflow records.
     */
    public function up(): void
    {
        Schema::table(
            'laboratory_reports',
            function (Blueprint $table): void {
                $table->uuid('lineage_uuid')
                    ->nullable()
                    ->after('uuid');

                $table->unsignedInteger('version')
                    ->nullable()
                    ->after('lineage_uuid');

                $table->foreignId('supersedes_report_id')
                    ->nullable()
                    ->after('laboratory_order_id');

                $table->text('amendment_reason')
                    ->nullable()
                    ->after('results_snapshot');

                $table->json('amendment_summary')
                    ->nullable()
                    ->after('amendment_reason');

                $table->foreignId('amended_by')
                    ->nullable()
                    ->after('amendment_summary');

                $table->json('amendment_actor_snapshot')
                    ->nullable()
                    ->after('amended_by');

                $table->timestampTz('amended_at')
                    ->nullable()
                    ->after('amendment_actor_snapshot');
            }
        );

        DB::connection('tenant')
            ->table('laboratory_reports')
            ->orderBy('id')
            ->chunkById(
                250,
                static function ($reports): void {
                    foreach ($reports as $report) {
                        DB::connection('tenant')
                            ->table('laboratory_reports')
                            ->where('id', $report->id)
                            ->update([
                                'lineage_uuid' => $report->uuid,
                                'version' => 1,
                            ]);
                    }
                }
            );

        Schema::table(
            'laboratory_reports',
            function (Blueprint $table): void {
                $table->uuid('lineage_uuid')
                    ->nullable(false)
                    ->change();

                $table->unsignedInteger('version')
                    ->nullable(false)
                    ->change();

                $table->unique(
                    [
                        'lineage_uuid',
                        'version',
                    ],
                    'lab_reports_lineage_version_unique'
                );

                $table->unique(
                    [
                        'laboratory_order_id',
                        'version',
                    ],
                    'lab_reports_order_version_unique'
                );

                $table->dropUnique([
                    'laboratory_order_id',
                ]);

                $table->unique(
                    'supersedes_report_id',
                    'lab_reports_predecessor_unique'
                );

                $table->foreign(
                    'supersedes_report_id',
                    'lab_reports_predecessor_foreign'
                )
                    ->references('id')
                    ->on('laboratory_reports')
                    ->restrictOnDelete();

                $table->foreign(
                    'amended_by',
                    'lab_reports_amended_by_foreign'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        );

        Schema::create(
            'laboratory_report_amendments',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();

                $table->foreignId('source_report_id');
                $table->foreignId('open_source_report_id')
                    ->nullable();
                $table->foreignId('target_report_id')
                    ->nullable();

                $table->string('status', 32);
                $table->text('reason');
                $table->json('change_summary');
                $table->json('proposed_patient_snapshot');
                $table->json('proposed_order_snapshot');
                $table->json('proposed_results_snapshot');

                $table->foreignId('requested_by')
                    ->nullable();
                $table->json('requester_snapshot');
                $table->timestampTz('requested_at');

                $table->foreignId('reviewed_by')
                    ->nullable();
                $table->json('reviewer_snapshot')
                    ->nullable();
                $table->timestampTz('reviewed_at')
                    ->nullable();
                $table->text('rejection_reason')
                    ->nullable();

                $table->timestampsTz();

                $table->unique(
                    'open_source_report_id',
                    'lab_report_amendments_open_source_unique'
                );

                $table->unique(
                    'target_report_id',
                    'lab_report_amendments_target_unique'
                );

                $table->index(
                    [
                        'source_report_id',
                        'status',
                    ],
                    'lab_report_amendments_source_status_index'
                );

                $table->foreign(
                    'source_report_id',
                    'lab_report_amendments_source_foreign'
                )
                    ->references('id')
                    ->on('laboratory_reports')
                    ->restrictOnDelete();

                $table->foreign(
                    'open_source_report_id',
                    'lab_report_amendments_open_source_foreign'
                )
                    ->references('id')
                    ->on('laboratory_reports')
                    ->restrictOnDelete();

                $table->foreign(
                    'target_report_id',
                    'lab_report_amendments_target_foreign'
                )
                    ->references('id')
                    ->on('laboratory_reports')
                    ->restrictOnDelete();

                $table->foreign('requested_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->foreign('reviewed_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        );

        Schema::create(
            'test_result_amendments',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();

                $table->foreignId(
                    'laboratory_report_amendment_id'
                );
                $table->foreignId('test_result_id');
                $table->unsignedInteger('version');
                $table->foreignId(
                    'supersedes_test_result_amendment_id'
                )->nullable();

                $table->char('source_payload_hash', 64);
                $table->json('corrected_results_data');
                $table->text('corrected_notes')->nullable();
                $table->text('reason');

                $table->foreignId('amended_by')
                    ->nullable();
                $table->json('amendment_actor_snapshot');
                $table->foreignId('approved_by')
                    ->nullable();
                $table->json('approval_actor_snapshot');
                $table->timestampTz('approved_at');
                $table->timestampTz('created_at')
                    ->useCurrent();

                $table->unique(
                    [
                        'test_result_id',
                        'version',
                    ],
                    'test_result_amendments_result_version_unique'
                );

                $table->unique(
                    [
                        'laboratory_report_amendment_id',
                        'test_result_id',
                    ],
                    'test_result_amendments_request_result_unique'
                );

                $table->unique(
                    'supersedes_test_result_amendment_id',
                    'test_result_amendments_predecessor_unique'
                );

                $table->foreign(
                    'laboratory_report_amendment_id',
                    'test_result_amendments_request_foreign'
                )
                    ->references('id')
                    ->on('laboratory_report_amendments')
                    ->restrictOnDelete();

                $table->foreign(
                    'test_result_id',
                    'test_result_amendments_result_foreign'
                )
                    ->references('id')
                    ->on('test_results')
                    ->restrictOnDelete();

                $table->foreign(
                    'supersedes_test_result_amendment_id',
                    'test_result_amendments_predecessor_foreign'
                )
                    ->references('id')
                    ->on('test_result_amendments')
                    ->restrictOnDelete();

                $table->foreign('amended_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();

                $table->foreign('approved_by')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        );
    }

    /**
     * Roll back only when no clinical amendment data exists.
     */
    public function down(): void
    {
        if (
            Schema::hasTable('test_result_amendments')
            && DB::connection('tenant')
                ->table('test_result_amendments')
                ->exists()
        ) {
            throw new RuntimeException(
                'Refusing to remove approved result-amendment history.'
            );
        }

        if (
            Schema::hasTable('laboratory_report_amendments')
            && DB::connection('tenant')
                ->table('laboratory_report_amendments')
                ->exists()
        ) {
            throw new RuntimeException(
                'Refusing to remove laboratory-report amendment history.'
            );
        }

        if (
            Schema::hasColumn('laboratory_reports', 'version')
            && DB::connection('tenant')
                ->table('laboratory_reports')
                ->where('version', '>', 1)
                ->exists()
        ) {
            throw new RuntimeException(
                'Refusing to collapse a report lineage with amendments.'
            );
        }

        Schema::dropIfExists('test_result_amendments');
        Schema::dropIfExists('laboratory_report_amendments');

        Schema::table(
            'laboratory_reports',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'lab_reports_predecessor_foreign'
                );
                $table->dropForeign(
                    'lab_reports_amended_by_foreign'
                );

                $table->unique('laboratory_order_id');

                $table->dropUnique(
                    'lab_reports_lineage_version_unique'
                );
                $table->dropUnique(
                    'lab_reports_order_version_unique'
                );
                $table->dropUnique(
                    'lab_reports_predecessor_unique'
                );

                $table->dropColumn([
                    'lineage_uuid',
                    'version',
                    'supersedes_report_id',
                    'amendment_reason',
                    'amendment_summary',
                    'amended_by',
                    'amendment_actor_snapshot',
                    'amended_at',
                ]);
            }
        );
    }
};
