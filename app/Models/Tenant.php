<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Multitenancy\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    protected $connection = 'landlord';

    protected $fillable = [
        'name',
        'domain',
        'database',
        'settings',
        'is_active',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    /**
     * Create the tenant database and run migrations
     */
    public static function createTenant(array $data)
    {
        $databaseName = 'tenant_' . uniqid() . '_' . strtolower(str_replace(' ', '-', $data['domain']));
        $databaseName = preg_replace('/[^a-zA-Z0-9_-]/', '', $databaseName);

        // Create database
        DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}`");

        // Create tenant record
        $tenant = self::create([
            'name' => $data['name'],
            'domain' => $data['domain'],
            'database' => $databaseName,
            'is_active' => true,
            'trial_ends_at' => now()->addDays(30),
        ]);

        // Switch to tenant database and run migrations
        $tenant->makeCurrent();
        config(['database.connections.tenant.database' => $databaseName]);
        DB::purge('tenant');
        DB::connection('tenant')->reconnect();

        // Run migrations for tenant
        \Artisan::call('migrate', [
            '--database' => 'tenant',
            '--path' => 'database/migrations/tenant',
            '--force' => true,
        ]);

        // Create Sanctum tables in tenant database
        $tenant->createSanctumTables();

        return $tenant;
    }

    /**
     * Create Sanctum tables in tenant database
     */
    public function createSanctumTables(): void
    {
        $schema = Schema::connection('tenant');
        
        if (!$schema->hasTable('personal_access_tokens')) {
            $schema->create('personal_access_tokens', function ($table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Switch to this tenant - FIXED return type to match parent
     */
    public function makeCurrent(): static
    {
        config([
            'database.connections.tenant.database' => $this->database,
            'database.default' => 'tenant',
        ]);
        
        DB::purge('tenant');
        DB::connection('tenant')->reconnect();
        
        parent::makeCurrent();
        
        // Set the auth guard to tenant
        config(['auth.defaults.guard' => 'tenant']);
        
        return $this;
    }
}