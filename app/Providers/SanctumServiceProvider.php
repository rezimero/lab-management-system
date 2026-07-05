<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use App\Models\PersonalAccessToken;

class SanctumServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Use your custom PersonalAccessToken model
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | IMPORTANT
        |--------------------------------------------------------------------------
        |
        | Do NOT override Sanctum authentication here.
        |
        | Tenant resolution is already handled by:
        |   1. HeaderTenantFinder
        |   2. SetTenantContext middleware
        |
        | Sanctum should simply authenticate against the already-selected
        | tenant connection.
        |
        | The previous implementation scanned every tenant database looking
        | for a matching user ID, which caused requests to authenticate
        | against the first tenant that contained the same user ID
        | (e.g. Advanced Diagnostics instead of Fego).
        |
        */
    }
}