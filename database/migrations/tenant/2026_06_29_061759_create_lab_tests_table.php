<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the tenant laboratory test catalogue.
     */
    public function up(): void
    {
        Schema::create(
            'lab_tests',
            function (Blueprint $table): void {
                $table->id();

                $table->string('name', 255);

                $table->text('description')
                    ->nullable();

                $table->string('category', 100);

                $table->decimal('price', 12, 2)
                    ->nullable()
                    ->default(0);

                $table->unsignedInteger(
                    'turnaround_time_hours'
                )->nullable();

                $table->json('parameters')
                    ->nullable();

                $table->boolean('is_active')
                    ->default(true);

                $table->timestamps();

                $table->index('category');
                $table->index('is_active');
                $table->index([
                    'category',
                    'is_active',
                ]);
            }
        );
    }

    /**
     * Drop the tenant laboratory test catalogue.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};
