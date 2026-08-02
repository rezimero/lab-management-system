<?php

use App\Support\Commercial\CommercialFeatureCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('code', 80)->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->char('currency', 3)->default('NGN');
            $table->decimal('monthly_price', 14, 2)->default(0);
            $table->decimal('annual_price', 14, 2)->default(0);
            $table->unsignedSmallInteger('trial_days')->default(30);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_public')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('plan_features', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plan_id')
                ->constrained('plans')
                ->cascadeOnDelete();
            $table->string('feature_key', 120);
            $table->string('name', 180);
            $table->boolean('is_enabled')->default(true);
            $table->unsignedBigInteger('limit_value')->nullable();
            $table->timestampsTz();

            $table->unique(['plan_id', 'feature_key']);
            $table->index(['feature_key', 'is_enabled']);
        });

        Schema::create('tenant_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();
            $table->foreignId('plan_id')
                ->constrained('plans')
                ->restrictOnDelete();
            $table->string('status', 32);
            $table->string('billing_interval', 16)->default('monthly');
            $table->timestampTz('starts_at');
            $table->timestampTz('trial_ends_at')->nullable();
            $table->timestampTz('current_period_starts_at')->nullable();
            $table->timestampTz('current_period_ends_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestampsTz();

            $table->index(['tenant_id', 'status']);
            $table->index(['current_period_ends_at', 'status']);
        });

        $now = now();
        $planId = DB::connection('landlord')
            ->table('plans')
            ->insertGetId([
                'uuid' => (string) Str::uuid(),
                'code' => 'commercial-beta',
                'name' => 'Commercial Beta',
                'description' => 'Commercial beta plan with features enabled only after each implementation milestone is validated.',
                'currency' => 'NGN',
                'monthly_price' => '0.00',
                'annual_price' => '0.00',
                'trial_days' => 30,
                'is_default' => true,
                'is_public' => false,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        foreach (CommercialFeatureCatalog::labels() as $key => $name) {
            DB::connection('landlord')
                ->table('plan_features')
                ->insert([
                    'plan_id' => $planId,
                    'feature_key' => $key,
                    'name' => $name,
                    'is_enabled' => in_array(
                        $key,
                        CommercialFeatureCatalog::enabledByDefault(),
                        true
                    ),
                    'limit_value' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        DB::connection('landlord')
            ->table('tenants')
            ->orderBy('id')
            ->chunkById(200, function ($tenants) use ($planId, $now): void {
                foreach ($tenants as $tenant) {
                    $trialEndsAt = $tenant->trial_ends_at
                        ?? now()->addDays(30);

                    DB::connection('landlord')
                        ->table('tenant_subscriptions')
                        ->insert([
                            'uuid' => (string) Str::uuid(),
                            'tenant_id' => $tenant->id,
                            'plan_id' => $planId,
                            'status' => 'trialing',
                            'billing_interval' => 'monthly',
                            'starts_at' => $tenant->created_at ?? $now,
                            'trial_ends_at' => $trialEndsAt,
                            'current_period_starts_at' => $tenant->created_at ?? $now,
                            'current_period_ends_at' => $trialEndsAt,
                            'metadata' => json_encode([
                                'source' => 'commercial_beta_backfill',
                            ], JSON_THROW_ON_ERROR),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
};
