<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add an explicit tenant boundary to centrally stored access tokens.
     */
    public function up(): void
    {
        if (
            ! Schema::connection('landlord')
                ->hasTable('personal_access_tokens')
        ) {
            throw new RuntimeException(
                'The landlord personal_access_tokens table does not exist.'
            );
        }

        if (
            ! Schema::connection('landlord')
                ->hasTable('tenants')
        ) {
            throw new RuntimeException(
                'The landlord tenants table does not exist.'
            );
        }

        if (
            ! Schema::connection('landlord')
                ->hasColumn('personal_access_tokens', 'tenant_id')
        ) {
            Schema::connection('landlord')
                ->table(
                    'personal_access_tokens',
                    function (Blueprint $table): void {
                        $table
                            ->foreignId('tenant_id')
                            ->nullable()
                            ->after('tokenable_id')
                            ->constrained('tenants')
                            ->cascadeOnDelete();

                        $table->index(
                            [
                                'tenant_id',
                                'tokenable_type',
                                'tokenable_id',
                            ],
                            'personal_access_tokens_tenant_tokenable_index'
                        );
                    }
                );
        }

        /*
         * Existing tenant tokens cannot be safely assigned to a tenant
         * because they currently contain only tokenable_type and tokenable_id.
         *
         * Revoke tenant tokens and require users to log in again.
         * Landlord and superadmin tokens are preserved.
         */
        DB::connection('landlord')
            ->table('personal_access_tokens')
            ->where(
                'tokenable_type',
                'App\\Models\\Tenant\\User'
            )
            ->delete();
    }

    /**
     * Remove the tenant token boundary.
     */
    public function down(): void
    {
        if (
            ! Schema::connection('landlord')
                ->hasColumn('personal_access_tokens', 'tenant_id')
        ) {
            return;
        }

        Schema::connection('landlord')
            ->table(
                'personal_access_tokens',
                function (Blueprint $table): void {
                    $table->dropForeign([
                        'tenant_id',
                    ]);

                    $table->dropIndex(
                        'personal_access_tokens_tenant_tokenable_index'
                    );

                    $table->dropColumn('tenant_id');
                }
            );
    }
};