<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the missing laboratory catalogue fields.
     */
    public function up(): void
    {
        Schema::table(
            'lab_tests',
            function (Blueprint $table): void {
                $table->string('name', 255)
                    ->default('Unnamed Test')
                    ->after('id');

                $table->text('description')
                    ->nullable()
                    ->after('name');

                $table->string('category', 100)
                    ->default('General')
                    ->after('description');

                $table->decimal('price', 12, 2)
                    ->nullable()
                    ->default(0)
                    ->after('category');

                $table->unsignedInteger(
                    'turnaround_time_hours'
                )
                    ->nullable()
                    ->after('price');

                $table->json('parameters')
                    ->nullable()
                    ->after('turnaround_time_hours');

                $table->boolean('is_active')
                    ->default(true)
                    ->after('parameters');

                $table->index('category');
                $table->index('is_active');
            }
        );
    }

    /**
     * Remove the laboratory catalogue fields.
     */
    public function down(): void
    {
        Schema::table(
            'lab_tests',
            function (Blueprint $table): void {
                $table->dropIndex(['category']);
                $table->dropIndex(['is_active']);

                $table->dropColumn([
                    'name',
                    'description',
                    'category',
                    'price',
                    'turnaround_time_hours',
                    'parameters',
                    'is_active',
                ]);
            }
        );
    }
};