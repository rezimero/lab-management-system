<?php

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\SetTenantContext;
use App\Http\Middleware\SuperAdminOverride;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
        |--------------------------------------------------------------------------
        | CSRF Configuration
        |--------------------------------------------------------------------------
        */

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Sanctum Stateful API Support
        |--------------------------------------------------------------------------
        */

        $middleware->statefulApi();

        /*
        |--------------------------------------------------------------------------
        | Middleware Aliases
        |--------------------------------------------------------------------------
        */

        $middleware->alias([
            'auth' => Authenticate::class,
            'role' => CheckRole::class,
            'permission' => CheckPermission::class,
            'superadmin' => SuperAdminOverride::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Sanctum Middleware Priority
        |--------------------------------------------------------------------------
        */

        $middleware->prependToPriorityList(
            before: [
                AuthenticatesRequests::class,
                SubstituteBindings::class,
            ],
            prepend: EnsureFrontendRequestsAreStateful::class,
        );

        /*
        |--------------------------------------------------------------------------
        | Tenant Context Priority
        |--------------------------------------------------------------------------
        |
        | Tenant resolution must happen before Sanctum authentication and
        | route-model binding whenever SetTenantContext is attached.
        |
        */

        $middleware->prependToPriorityList(
            before: EnsureFrontendRequestsAreStateful::class,
            prepend: SetTenantContext::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*'),
        );
    })
    ->create();