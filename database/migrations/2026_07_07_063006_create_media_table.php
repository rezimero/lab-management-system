<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'landlord';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('landlord')->create('media', function (Blueprint $table) {

            $table->id();

            // External identifier
            $table->uuid('uuid')->unique();

            // Tenant ownership
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            // User who uploaded it
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Polymorphic Owner
            |--------------------------------------------------------------------------
            |
            | TenantProfile
            | Patient
            | TestResult
            | Invoice
            | User
            |
            */

            $table->nullableMorphs('mediaable');

            // Collection
            $table->string('collection');

            // Storage
            $table->string('disk')->default('public');

            $table->string('directory');

            $table->string('filename');

            $table->string('original_filename');

            $table->string('extension', 20);

            $table->string('mime_type');

            $table->unsignedBigInteger('size');

            $table->string('path');

            // SHA256 checksum
            $table->string('hash', 64)->nullable();

            // Extra information
            $table->json('metadata')->nullable();

            $table->softDeletes();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexes
            |--------------------------------------------------------------------------
            */

            $table->index(['tenant_id', 'collection']);
            $table->index('uploaded_by');
            $table->index('uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('media');
    }
};