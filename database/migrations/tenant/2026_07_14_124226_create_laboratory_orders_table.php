<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create tenant laboratory orders.
     */
    public function up(): void
    {
        Schema::create(
            'laboratory_orders',
            function (Blueprint $table): void {
                $table->id();

                $table->uuid('uuid')
                    ->unique();

                $table->string(
                    'order_number',
                    40
                )->unique();

                $table->foreignId('patient_id')
                    ->constrained('patients')
                    ->restrictOnDelete();

                $table->foreignId('ordered_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string(
                    'referring_clinician',
                    255
                )->nullable();

                $table->enum('priority', [
                    'routine',
                    'urgent',
                    'stat',
                ])->default('routine');

                $table->enum('status', [
                    'pending',
                    'sample_collected',
                    'in_progress',
                    'completed',
                    'cancelled',
                ])->default('pending');

                $table->text('clinical_notes')
                    ->nullable();

                $table->decimal(
                    'total_amount',
                    12,
                    2
                )->default(0);

                $table->timestampTz('ordered_at');

                $table->timestampTz(
                    'sample_collected_at'
                )->nullable();

                $table->timestampTz(
                    'completed_at'
                )->nullable();

                $table->timestampTz(
                    'cancelled_at'
                )->nullable();

                $table->timestampsTz();
                $table->softDeletesTz();

                $table->index('patient_id');
                $table->index('ordered_by');
                $table->index('priority');
                $table->index('status');
                $table->index('ordered_at');

                $table->index([
                    'patient_id',
                    'status',
                ]);
            }
        );
    }

    /**
     * Drop tenant laboratory orders.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'laboratory_orders'
        );
    }
};
