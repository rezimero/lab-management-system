<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link specimens to laboratory order items.
     */
    public function up(): void
    {
        Schema::create(
            'laboratory_order_item_specimen',
            function (Blueprint $table): void {
                $table->id();

                $table->unsignedBigInteger(
                    'laboratory_order_item_id'
                );

                $table->unsignedBigInteger(
                    'specimen_id'
                );

                $table->timestampsTz();

                $table->foreign(
                    'laboratory_order_item_id',
                    'lois_order_item_foreign'
                )
                    ->references('id')
                    ->on('laboratory_order_items')
                    ->cascadeOnDelete();

                $table->foreign(
                    'specimen_id',
                    'lois_specimen_foreign'
                )
                    ->references('id')
                    ->on('specimens')
                    ->cascadeOnDelete();

                $table->unique(
                    [
                        'laboratory_order_item_id',
                        'specimen_id',
                    ],
                    'lois_order_item_specimen_unique'
                );
            }
        );
    }

    /**
     * Drop specimen-to-order-item links.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'laboratory_order_item_specimen'
        );
    }
};
