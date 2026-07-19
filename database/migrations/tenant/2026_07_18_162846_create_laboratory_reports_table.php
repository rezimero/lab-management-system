<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create immutable tenant laboratory-report snapshots.
     */
    public function up(): void
    {
        Schema::create(
            'laboratory_reports',
            function (Blueprint $table): void {
                $table->id();

                $table->uuid('uuid')
                    ->unique();

                $table->string(
                    'report_number',
                    150
                )->unique();

                $table->foreignId(
                    'laboratory_order_id'
                )
                    ->unique()
                    ->constrained(
                        'laboratory_orders'
                    )
                    ->restrictOnDelete();

                $table->foreignId(
                    'generated_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->json(
                    'laboratory_snapshot'
                );

                $table->json(
                    'patient_snapshot'
                );

                $table->json(
                    'order_snapshot'
                );

                $table->json(
                    'results_snapshot'
                );

                $table->timestampTz(
                    'generated_at'
                );

                $table->timestampsTz();

                $table->index(
                    'generated_at'
                );
            }
        );
    }

    /**
     * Drop tenant laboratory reports.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'laboratory_reports'
        );
    }
};