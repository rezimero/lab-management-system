<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table(
            'laboratory_orders',
            function (Blueprint $table): void {
                $table->foreignId('referring_clinician_id')
                    ->nullable()
                    ->constrained('referring_clinicians')
                    ->nullOnDelete();

                $table->index(
                    ['referring_clinician_id', 'ordered_at'],
                    'laboratory_orders_ref_clinician_ordered_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::connection('tenant')->table(
            'laboratory_orders',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'laboratory_orders_ref_clinician_ordered_idx'
                );

                $table->dropConstrainedForeignId(
                    'referring_clinician_id'
                );
            }
        );
    }
};
