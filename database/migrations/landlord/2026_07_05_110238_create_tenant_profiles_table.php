<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        Schema::connection('landlord')->create('tenant_profiles', function (Blueprint $table) {

            $table->id();

            $table->foreignId('tenant_id')
                ->unique()
                ->constrained('tenants')
                ->cascadeOnDelete();

            // Identity
            $table->string('lab_name')->nullable();
            $table->string('tagline')->nullable();

            // Branding
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();

            $table->string('primary_color')->default('#1976d2');
            $table->string('secondary_color')->default('#424242');
            $table->string('accent_color')->default('#2e7d32');

            // Contact
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();

            // Address
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();

            // Legal
            $table->string('license_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('tin')->nullable();

            // Reports
            $table->text('report_footer')->nullable();
            $table->string('director_name')->nullable();
            $table->string('director_signature')->nullable();

            // Invoices
            $table->text('invoice_footer')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('tenant_profiles');
    }
};