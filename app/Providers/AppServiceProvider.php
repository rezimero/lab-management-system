<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(
            PersonalAccessToken::class
        );

        RateLimiter::for(
            'portal-login',
            function (Request $request): Limit {
                $tenantId = $request->attributes
                    ->get('tenant')
                    ?->getKey();
                $email = strtolower(trim(
                    (string) $request->input('email')
                ));

                return Limit::perMinute(6)->by(
                    hash('sha256', implode('|', [
                        (string) $tenantId,
                        $request->path(),
                        $email,
                        (string) $request->ip(),
                    ]))
                );
            }
        );

        RateLimiter::for(
            'portal-activation',
            function (Request $request): Limit {
                $tenantId = $request->attributes
                    ->get('tenant')
                    ?->getKey();
                $tokenFingerprint = hash(
                    'sha256',
                    trim((string) $request->input('token'))
                );

                return Limit::perMinute(10)->by(
                    hash('sha256', implode('|', [
                        (string) $tenantId,
                        $tokenFingerprint,
                        (string) $request->ip(),
                    ]))
                );
            }
        );

        RateLimiter::for(
            'portal-password-reset-request',
            function (Request $request): Limit {
                $tenantId = $request->attributes
                    ->get('tenant')
                    ?->getKey();
                $email = strtolower(trim(
                    (string) $request->input('email')
                ));
                $accountType = strtolower(trim(
                    (string) $request->input('account_type')
                ));

                return Limit::perMinute(3)->by(
                    hash('sha256', implode('|', [
                        (string) $tenantId,
                        $accountType,
                        $email,
                        (string) $request->ip(),
                    ]))
                );
            }
        );

        RateLimiter::for(
            'portal-password-reset-complete',
            function (Request $request): Limit {
                $tenantId = $request->attributes
                    ->get('tenant')
                    ?->getKey();
                $tokenFingerprint = hash(
                    'sha256',
                    trim((string) $request->input('token'))
                );

                return Limit::perMinute(10)->by(
                    hash('sha256', implode('|', [
                        (string) $tenantId,
                        $tokenFingerprint,
                        (string) $request->ip(),
                    ]))
                );
            }
        );

        $this->loadMigrationsFrom(
            database_path(
                'migrations/landlord'
            )
        );
    }
}
