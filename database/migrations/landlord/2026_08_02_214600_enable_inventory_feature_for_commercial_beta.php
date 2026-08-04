<?php

use App\Support\Commercial\CommercialFeatureCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'landlord';

    public function up(): void
    {
        DB::connection('landlord')
            ->table('plan_features')
            ->where('feature_key', CommercialFeatureCatalog::INVENTORY)
            ->whereIn('plan_id', function ($query): void {
                $query->select('id')
                    ->from('plans')
                    ->where('code', 'commercial-beta');
            })
            ->update([
                'is_enabled' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::connection('landlord')
            ->table('plan_features')
            ->where('feature_key', CommercialFeatureCatalog::INVENTORY)
            ->whereIn('plan_id', function ($query): void {
                $query->select('id')
                    ->from('plans')
                    ->where('code', 'commercial-beta');
            })
            ->update([
                'is_enabled' => false,
                'updated_at' => now(),
            ]);
    }
};
