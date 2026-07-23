<?php

namespace App\Services\Authentication;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantSwitcher
{
    /**
     * Switch the application into a tenant context.
     */
    public function switch(Tenant $tenant): Tenant
    {
        $this->configureTenantConnection($tenant);

        DB::purge('tenant');

        DB::connection('tenant')->reconnect();

        Config::set('database.default', 'tenant');

        $tenant->makeCurrent();

        session([
            'tenant_id' => $tenant->id,
            'tenant_domain' => $tenant->domain,
        ]);

        Log::info('Tenant context initialized.', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'database' => $tenant->database,
        ]);

        return $tenant;
    }

    /**
     * Restore the application back to landlord context.
     */
    public function restore(): void
    {
        DB::disconnect('tenant');

        Config::set('database.default', 'landlord');
        Config::set('auth.defaults.guard', 'landlord');

        Auth::forgetGuards();

        DB::purge('landlord');

        DB::connection('landlord')->reconnect();

        Tenant::forgetCurrent();

        session()->forget([
            'tenant_id',
            'tenant_domain',
        ]);

        Log::info('Returned to landlord context.');
    }

    /**
     * Get the current tenant.
     */
    public function current(): ?Tenant
    {
        return Tenant::current();
    }

    /**
     * Determine whether a tenant context exists.
     */
    public function hasTenant(): bool
    {
        return $this->current() !== null;
    }

    /**
     * Get the active tenant database name.
     */
    public function database(): ?string
    {
        return $this->current()?->database;
    }

    /**
     * Configure the tenant database connection.
     */
    protected function configureTenantConnection(Tenant $tenant): void
    {
        Config::set('database.connections.tenant', [

            'driver' => 'mysql',

            'host' => config('database.connections.landlord.host'),

            'port' => config('database.connections.landlord.port'),

            'database' => $tenant->database,

            'username' => config('database.connections.landlord.username'),

            'password' => config('database.connections.landlord.password'),

            'charset' => 'utf8mb4',

            'collation' => 'utf8mb4_unicode_ci',

            'prefix' => '',

            'strict' => true,

            'engine' => null,

        ]);
    }
}
