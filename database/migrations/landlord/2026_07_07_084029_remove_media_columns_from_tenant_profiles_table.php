<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        Schema::connection('landlord')
            ->table('tenant_profiles', function (Blueprint $table) {

                $table->dropColumn([
                    'logo',
                    'favicon',
                    'director_signature',
                ]);

            });
    }

    public function down(): void
    {
        Schema::connection('landlord')
            ->table('tenant_profiles', function (Blueprint $table) {

                $table->string('logo')->nullable();

                $table->string('favicon')->nullable();

                $table->string('director_signature')->nullable();

            });
    }
};