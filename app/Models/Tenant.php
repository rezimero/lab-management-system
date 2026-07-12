<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
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

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Laboratory profile.
     */
    public function profile()
    {
        return $this->hasOne(TenantProfile::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Current Tenant
    |--------------------------------------------------------------------------
    */

    /**
     * Switch the application into this tenant's context.
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

        config([
            'auth.defaults.guard' => 'tenant',
        ]);

        return $this;
    }
}