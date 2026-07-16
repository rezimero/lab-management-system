<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add rejected-specimen replacement lineage.
     */
    public function up(): void
    {
        Schema::table(
            'specimens',
            function (Blueprint $table): void {
                $table->foreignId(
                    'replaces_specimen_id'
                )
                    ->nullable()
                    ->after('laboratory_order_id');

                $table->foreign(
                    'replaces_specimen_id',
                    'specimens_replaces_foreign'
                )
                    ->references('id')
                    ->on('specimens')
                    ->nullOnDelete();

                $table->unique(
                    'replaces_specimen_id',
                    'specimens_replaces_unique'
                );
            }
        );
    }

    /**
     * Remove rejected-specimen replacement lineage.
     */
    public function down(): void
    {
        Schema::table(
            'specimens',
            function (Blueprint $table): void {
                $table->dropForeign(
                    'specimens_replaces_foreign'
                );

                $table->dropUnique(
                    'specimens_replaces_unique'
                );

                $table->dropColumn(
                    'replaces_specimen_id'
                );
            }
        );
    }
};
