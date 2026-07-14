<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create tenant laboratory order items.
     */
    public function up(): void
    {
        Schema::create(
            'laboratory_order_items',
            function (Blueprint $table): void {
                $table->id();

                $table->uuid('uuid')
                    ->unique();

                $table->foreignId(
                    'laboratory_order_id'
                )
                    ->constrained(
                        'laboratory_orders'
                    )
                    ->cascadeOnDelete();

                $table->foreignId('lab_test_id')
                    ->nullable()
                    ->constrained('lab_tests')
                    ->nullOnDelete();

                $table->string(
                    'test_name_snapshot',
                    255
                );

                $table->string(
                    'test_category_snapshot',
                    100
                );

                $table->decimal(
                    'price_snapshot',
                    12,
                    2
                )->default(0);

                $table->unsignedInteger(
                    'turnaround_time_hours_snapshot'
                )->nullable();

                $table->json(
                    'parameters_snapshot'
                )->nullable();

                $table->enum('status', [
                    'pending',
                    'in_progress',
                    'completed',
                    'cancelled',
                ])->default('pending');

                $table->timestampsTz();

                $table->index(
                    'laboratory_order_id'
                );

                $table->index('lab_test_id');
                $table->index('status');

                $table->unique([
                    'laboratory_order_id',
                    'lab_test_id',
                ]);
            }
        );
    }

    /**
     * Drop tenant laboratory order items.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'laboratory_order_items'
        );
    }
};
