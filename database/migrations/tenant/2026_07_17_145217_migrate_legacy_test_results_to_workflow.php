<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Convert legacy result records to workflow states.
     */
    public function up(): void
    {
        $connection = DB::connection('tenant');

        $connection
            ->table('test_results')
            ->select('id')
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunkById(
                100,
                function ($rows) use (
                    $connection
                ): void {
                    foreach ($rows as $row) {
                        $connection
                            ->table('test_results')
                            ->where('id', $row->id)
                            ->update([
                                'uuid' => (
                                    (string) Str::uuid()
                                ),
                            ]);
                    }
                }
            );

        $connection
            ->table('test_results')
            ->whereNull('entered_by')
            ->whereNotNull('user_id')
            ->update([
                'entered_by' => DB::raw(
                    '`user_id`'
                ),
            ]);

        $connection
            ->table('test_results')
            ->where('status', 'in_progress')
            ->update([
                'submitted_by' => DB::raw(
                    'COALESCE(`submitted_by`, `user_id`)'
                ),
                'submitted_at' => DB::raw(
                    'COALESCE(`submitted_at`, `updated_at`, `created_at`)'
                ),
            ]);

        $connection
            ->table('test_results')
            ->where('status', 'completed')
            ->update([
                'submitted_by' => DB::raw(
                    'COALESCE(`submitted_by`, `user_id`)'
                ),
                'reviewed_by' => DB::raw(
                    'COALESCE(`reviewed_by`, `user_id`)'
                ),
                'submitted_at' => DB::raw(
                    'COALESCE(`submitted_at`, `completed_at`, `updated_at`, `created_at`)'
                ),
                'reviewed_at' => DB::raw(
                    'COALESCE(`reviewed_at`, `completed_at`, `updated_at`, `created_at`)'
                ),
            ]);

        $connection
            ->table('test_results')
            ->where('status', 'cancelled')
            ->update([
                'submitted_by' => DB::raw(
                    'COALESCE(`submitted_by`, `user_id`)'
                ),
                'reviewed_by' => DB::raw(
                    'COALESCE(`reviewed_by`, `user_id`)'
                ),
                'submitted_at' => DB::raw(
                    'COALESCE(`submitted_at`, `updated_at`, `created_at`)'
                ),
                'reviewed_at' => DB::raw(
                    'COALESCE(`reviewed_at`, `updated_at`, `created_at`)'
                ),
                'rejection_reason' => (
                    'Migrated from a legacy cancelled result.'
                ),
            ]);

        $connection
            ->table('test_results')
            ->where('status', 'pending')
            ->update([
                'status' => 'draft',
            ]);

        $connection
            ->table('test_results')
            ->where('status', 'in_progress')
            ->update([
                'status' => 'submitted',
            ]);

        $connection
            ->table('test_results')
            ->where('status', 'completed')
            ->update([
                'status' => 'approved',
            ]);

        $connection
            ->table('test_results')
            ->where('status', 'cancelled')
            ->update([
                'status' => 'rejected',
            ]);
    }

    /**
     * Restore workflow records to legacy-compatible data.
     */
    public function down(): void
    {
        $connection = DB::connection('tenant');

        $connection->statement(
            <<<'SQL'
UPDATE `test_results` AS `results`
LEFT JOIN `laboratory_order_items` AS `items`
    ON `items`.`id`
        = `results`.`laboratory_order_item_id`
LEFT JOIN `laboratory_orders` AS `orders`
    ON `orders`.`id`
        = `items`.`laboratory_order_id`
SET
    `results`.`patient_id`
        = COALESCE(
            `results`.`patient_id`,
            `orders`.`patient_id`
        ),
    `results`.`lab_test_id`
        = COALESCE(
            `results`.`lab_test_id`,
            `items`.`lab_test_id`
        ),
    `results`.`user_id`
        = COALESCE(
            `results`.`user_id`,
            `results`.`entered_by`,
            `results`.`submitted_by`,
            `results`.`reviewed_by`
        ),
    `results`.`test_date`
        = COALESCE(
            `results`.`test_date`,
            DATE(`results`.`created_at`)
        ),
    `results`.`completed_at`
        = CASE
            WHEN `results`.`status` = 'approved'
            THEN COALESCE(
                `results`.`completed_at`,
                `results`.`reviewed_at`,
                `results`.`updated_at`
            )
            ELSE `results`.`completed_at`
        END
SQL
        );

        $connection
            ->table('test_results')
            ->where('status', 'draft')
            ->update([
                'status' => 'pending',
            ]);

        $connection
            ->table('test_results')
            ->where('status', 'submitted')
            ->update([
                'status' => 'in_progress',
            ]);

        $connection
            ->table('test_results')
            ->where('status', 'approved')
            ->update([
                'status' => 'completed',
            ]);

        $connection
            ->table('test_results')
            ->where('status', 'rejected')
            ->update([
                'status' => 'cancelled',
            ]);
    }
};
