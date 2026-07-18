<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enforce the final result-workflow constraints.
     */
    public function up(): void
    {
        $this->assertDataIsReady();

        Schema::connection('tenant')->table(
            'test_results',
            function (Blueprint $table): void {
                $table->unique(
                    'uuid',
                    'test_results_uuid_unique'
                );

                $table->unique(
                    'laboratory_order_item_id',
                    'test_results_order_item_unique'
                );
            }
        );

        $connection = DB::connection('tenant');

        $connection->statement(
            <<<'SQL'
ALTER TABLE `test_results`
MODIFY `uuid` CHAR(36) NOT NULL
SQL
        );

        $connection->statement(
            <<<'SQL'
ALTER TABLE `test_results`
MODIFY `status`
ENUM(
    'draft',
    'submitted',
    'approved',
    'rejected'
)
NOT NULL DEFAULT 'draft'
SQL
        );
    }

    /**
     * Relax workflow constraints for rollback.
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
    'cancelled',
    'draft',
    'submitted',
    'approved',
    'rejected'
)
NOT NULL DEFAULT 'draft'
SQL
        );

        $connection->statement(
            <<<'SQL'
ALTER TABLE `test_results`
MODIFY `uuid` CHAR(36) NULL
SQL
        );

        Schema::connection('tenant')->table(
            'test_results',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'test_results_uuid_unique'
                );

                $table->dropUnique(
                    'test_results_order_item_unique'
                );
            }
        );
    }

    /**
     * Confirm that constraints can be applied safely.
     */
    private function assertDataIsReady(): void
    {
        $results = DB::connection('tenant')
            ->table('test_results');

        if (
            (clone $results)
                ->whereNull('uuid')
                ->exists()
        ) {
            throw new \RuntimeException(
                'Test-result UUID backfill is incomplete.'
            );
        }

        $duplicateUuid = (clone $results)
            ->select('uuid')
            ->groupBy('uuid')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateUuid) {
            throw new \RuntimeException(
                'Duplicate test-result UUIDs were found.'
            );
        }

        $duplicateOrderItem = (clone $results)
            ->select('laboratory_order_item_id')
            ->whereNotNull(
                'laboratory_order_item_id'
            )
            ->groupBy(
                'laboratory_order_item_id'
            )
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateOrderItem) {
            throw new \RuntimeException(
                'Multiple results reference the same order item.'
            );
        }

        $invalidStatus = (clone $results)
            ->whereNotIn(
                'status',
                [
                    'draft',
                    'submitted',
                    'approved',
                    'rejected',
                ]
            )
            ->exists();

        if ($invalidStatus) {
            throw new \RuntimeException(
                'Unsupported test-result statuses remain.'
            );
        }
    }
};
