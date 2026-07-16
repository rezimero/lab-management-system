<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create tenant specimen records.
     */
    public function up(): void
    {
        Schema::create(
            'specimens',
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

                $table->string(
                    'accession_number',
                    50
                )->unique();

                $table->string(
                    'specimen_type',
                    100
                );

                $table->string(
                    'container_type',
                    100
                )->nullable();

                $table->string(
                    'collection_site',
                    100
                )->nullable();

                $table->decimal(
                    'volume',
                    8,
                    2
                )->nullable();

                $table->string(
                    'volume_unit',
                    20
                )->nullable();

                $table->enum('status', [
                    'collected',
                    'received',
                    'rejected',
                ])->default('collected');

                $table->foreignId(
                    'collected_by'
                )
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestampTz(
                    'collected_at'
                );

                $table->timestampTz(
                    'received_at'
                )->nullable();

                $table->timestampTz(
                    'rejected_at'
                )->nullable();

                $table->text(
                    'rejection_reason'
                )->nullable();

                $table->text('notes')
                    ->nullable();

                $table->timestampsTz();

                $table->index(
                    'status'
                );

                $table->index(
                    'collected_at'
                );

                $table->index(
                    [
                        'laboratory_order_id',
                        'status',
                    ],
                    'specimens_order_status_index'
                );
            }
        );
    }

    /**
     * Drop tenant specimen records.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'specimens'
        );
    }
};
