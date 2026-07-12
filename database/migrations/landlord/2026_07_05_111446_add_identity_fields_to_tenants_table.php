<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('landlord')
            ->table('tenants', function (Blueprint $table) {

                $table->string('logo')->nullable()->after('name');

                $table->string('primary_color',20)
                    ->default('#2563EB')
                    ->after('logo');

                $table->string('secondary_color',20)
                    ->default('#1E293B')
                    ->after('primary_color');

                $table->string('phone')->nullable()->after('secondary_color');

                $table->string('email')->nullable()->after('phone');

                $table->string('website')->nullable()->after('email');

                $table->text('address')->nullable()->after('website');

                $table->string('city')->nullable()->after('address');

                $table->string('state')->nullable()->after('city');

                $table->string('country')->default('Nigeria')->after('state');

                $table->string('postal_code')->nullable()->after('country');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('landlord')
            ->table('tenants', function (Blueprint $table) {

                $table->dropColumn([
                    'logo',
                    'primary_color',
                    'secondary_color',
                    'phone',
                    'email',
                    'website',
                    'address',
                    'city',
                    'state',
                    'country',
                    'postal_code'
                ]);
            });
    }
};