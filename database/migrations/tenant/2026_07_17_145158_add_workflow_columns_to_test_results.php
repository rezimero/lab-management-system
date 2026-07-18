<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add workflow fields while preserving legacy records.
     */
    public function up(): void
    {
        Schema::connection('tenant')->table(
            'test_results',
            function (Blueprint $table): void {
                $table->uuid('uuid')
                    ->nullable()
                    ->after('id');

                $table->foreignId(
                    'laboratory_order_item_id'
                )
                    ->nullable()
                    ->after('uuid')
                    ->constrained(
                        'laboratory_order_items'
                    )
                    ->cascadeOnDelete();

                $table->foreignId('entered_by')
                    ->nullable()
                    ->after('status')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('submitted_by')
                    ->nullable()
                    ->after('entered_by')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('reviewed_by')
                    ->nullable()
                    ->after('submitted_by')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('submitted_at')
                    ->nullable()
                    ->after('reviewed_by');

                $table->timestamp('reviewed_at')
                    ->nullable()
                    ->after('submitted_at');

                $table->text('rejection_reason')
                    ->nullable()
                    ->after('reviewed_at');

                $table->index(
                    'status',
                    'test_results_status_index'
                );

                $table->index(
                    'submitted_at',
                    'test_results_submitted_at_index'
                );

                $table->index(
                    'reviewed_at',
                    'test_results_reviewed_at_index'
                );
            }
        );

        DB::connection('tenant')->statement(
            <<<'SQL'
ALTER TABLE `test_results`
MODIFY `status`
ENUM(
    'pending',
    'in_progress',
    'completed',
    'cancelled',
    'draft',
    'submitted',
    'approved',
    'rejected'
)
NOT NULL DEFAULT 'draft'
SQL
        );

        DB::connection('tenant')->statement(
            <<<'SQL'
ALTER TABLE `test_results`
MODIFY `patient_id` BIGINT UNSIGNED NULL,
MODIFY `lab_test_id` BIGINT UNSIGNED NULL,
MODIFY `user_id` BIGINT UNSIGNED NULL,
MODIFY `test_date` DATE NULL
SQL
        );
    }

    /**
     * Remove the additive workflow schema.
     */
    public function down(): void
    {
        $connection = DB::connection('tenant');

        $connection->statement(
            <<<'SQL'
ALTER TABLE `test_results`
MODIFY `status`
ENUM(
    'pending',
    'in_progress',
    'completed',
    'cancelled'
)
NOT NULL DEFAULT 'pending'
SQL
        );

        $connection->statement(
            <<<'SQL'
ALTER TABLE `test_results`
MODIFY `patient_id` BIGINT UNSIGNED NOT NULL,
MODIFY `lab_test_id` BIGINT UNSIGNED NOT NULL,
MODIFY `user_id` BIGINT UNSIGNED NOT NULL,
MODIFY `test_date` DATE NOT NULL
SQL
        );

        Schema::connection('tenant')->table(
            'test_results',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'test_results_status_index'
                );

                $table->dropIndex(
                    'test_results_submitted_at_index'
                );

                $table->dropIndex(
                    'test_results_reviewed_at_index'
                );

                $table->dropForeign([
                    'laboratory_order_item_id',
                ]);

                $table->dropForeign([
                    'entered_by',
                ]);

                $table->dropForeign([
                    'submitted_by',
                ]);

                $table->dropForeign([
                    'reviewed_by',
                ]);

                $table->dropColumn([
                    'uuid',
                    'laboratory_order_item_id',
                    'entered_by',
                    'submitted_by',
                    'reviewed_by',
                    'submitted_at',
                    'reviewed_at',
                    'rejection_reason',
                ]);
            }
        );
    }
};
