<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'landlord';

    /**
     * Add report-signatory details to tenant profiles.
     */
    public function up(): void
    {
        Schema::connection('landlord')
            ->table(
                'tenant_profiles',
                function (Blueprint $table): void {
                    $table
                        ->string('director_qualification')
                        ->nullable()
                        ->after('director_name');

                    $table
                        ->string(
                            'director_registration_number',
                            150
                        )
                        ->nullable()
                        ->after('director_qualification');

                    $table
                        ->string('signatory_designation')
                        ->nullable()
                        ->after(
                            'director_registration_number'
                        );

                    $table
                        ->text('accreditation_details')
                        ->nullable()
                        ->after('signatory_designation');
                }
            );
    }

    /**
     * Remove report-signatory details.
     */
    public function down(): void
    {
        Schema::connection('landlord')
            ->table(
                'tenant_profiles',
                function (Blueprint $table): void {
                    $table->dropColumn([
                        'director_qualification',
                        'director_registration_number',
                        'signatory_designation',
                        'accreditation_details',
                    ]);
                }
            );
    }
};