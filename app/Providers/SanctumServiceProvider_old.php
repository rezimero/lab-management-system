<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SanctumServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Use custom PersonalAccessToken model
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }

    public function boot(): void
    {
        // Override Sanctum's authentication to handle tenant context
        Auth::viaRequest('sanctum', function (Request $request) {
            $token = $request->bearerToken();
            
            if (!$token) {
                return null;
            }

            // Find the token in landlord database
            $tokenParts = explode('|', $token);
            $tokenId = $tokenParts[0] ?? null;
            
            if (!$tokenId) {
                return null;
            }

            // Get the token from landlord database
            $accessToken = PersonalAccessToken::find($tokenId);
            
            if (!$accessToken || !$accessToken->token || !hash_equals($accessToken->token, hash('sha256', $tokenParts[1] ?? ''))) {
                return null;
            }

            // Find which tenant this user belongs to
            $tenants = \App\Models\Tenant::all();
            $user = null;
            
            foreach ($tenants as $tenant) {
                // Switch to this tenant's database
                config(['database.connections.tenant.database' => $tenant->database]);
                \DB::purge('tenant');
                \DB::connection('tenant')->reconnect();
                $tenant->makeCurrent();
                
                // Try to find the user
                $user = User::where('id', $accessToken->tokenable_id)->first();
                
                if ($user) {
                    \Log::info('User found in tenant: ' . $tenant->name);
                    break;
                }
            }

            if (!$user) {
                return null;
            }

            // Set the tenant in session
            session(['tenant_id' => $tenant->id]);
            session(['domain' => $tenant->domain]);

            return $user;
        });
    }
}