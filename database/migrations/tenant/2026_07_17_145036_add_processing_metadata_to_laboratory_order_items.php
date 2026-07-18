<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add processing audit information to order items.
     */
    public function up(): void
    {
        Schema::connection('tenant')->table(
            'laboratory_order_items',
            function (Blueprint $table): void {
                $table->foreignId(
                    'processing_started_by'
                )
                    ->nullable()
                    ->after('status')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp(
                    'processing_started_at'
                )
                    ->nullable()
                    ->after(
                        'processing_started_by'
                    );

                $table->timestamp('completed_at')
                    ->nullable()
                    ->after(
                        'processing_started_at'
                    );
            }
        );
    }

    /**
     * Remove order-item processing metadata.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table(
            'laboratory_order_items',
            function (Blueprint $table): void {
                $table->dropForeign([
                    'processing_started_by',
                ]);

                $table->dropColumn([
                    'processing_started_by',
                    'processing_started_at',
                    'completed_at',
                ]);
            }
        );
    }
};
