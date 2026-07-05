<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Request $request): void
    {
        // 1. Force Sanctum to look for tokens in the centralized landlord database
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Prevent breaking console, migration, or seeding tasks
        if (app()->runningInConsole()) {
            return;
        }

        // 🌟 THE ULTIMATE BYPASS: Capture the header immediately at core boot
        $domain = $request->header('X-Tenant-Domain') 
            ?? $request->input('domain') 
            ?? $request->route('domain');

        if ($domain) {
            // Find the tenant matching the explicit header
            $tenant = Tenant::where('domain', $domain)->first();
            
            if ($tenant) {
                $host = Config::get('database.connections.landlord.host', '127.0.0.1');
                $port = Config::get('database.connections.landlord.port', '3306');
                $username = Config::get('database.connections.landlord.username', 'root');
                $password = Config::get('database.connections.landlord.password', '');
                
                // Rewrite BOTH connection footprints to point directly to the target database
                foreach (['tenant', 'mysql'] as $connectionName) {
                    Config::set("database.connections.{$connectionName}.host", $host);
                    Config::set("database.connections.{$connectionName}.port", $port);
                    Config::set("database.connections.{$connectionName}.database", $tenant->database);
                    Config::set("database.connections.{$connectionName}.username", $username);
                    Config::set("database.connections.{$connectionName}.password", $password);
                    Config::set("database.connections.{$connectionName}.driver", 'mysql');
                    
                    DB::purge($connectionName);
                }

                Config::set('database.default', 'tenant');
                
                // Force Spatie to drop Tenant 1 and make the header tenant current
                $tenant->makeCurrent();
                
                Log::info("🚀 Core Provider hijacked Spatie early. Switched to: [{$tenant->database}]");
            }
        }
    }
}