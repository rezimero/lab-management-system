<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table(
            'analyzer_result_imports',
            function (Blueprint $table): void {
                $table->unique(
                    'test_result_id',
                    'analyzer_result_import_test_result_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::connection('tenant')->table(
            'analyzer_result_imports',
            function (Blueprint $table): void {
                $table->dropUnique(
                    'analyzer_result_import_test_result_unique'
                );
            }
        );
    }
};
