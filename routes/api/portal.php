<?php

use App\Http\Controllers\Api\Portal\ClinicianPortalAuthController;
use App\Http\Controllers\Api\Portal\ClinicianPortalReadController;
use App\Http\Controllers\Api\Portal\PatientPortalAuthController;
use App\Http\Controllers\Api\Portal\PatientPortalReadController;
use App\Http\Controllers\Api\Portal\PortalInvitationActivationController;
use App\Http\Controllers\Api\Portal\PortalTenantContextController;
use App\Http\Middleware\AuthenticatePortalAccessToken;
use App\Http\Middleware\SetTenantContext;
use App\Models\PortalAccessToken;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->group(function (): void {
    Route::get('/context', [
        PortalTenantContextController::class,
        'show',
    ])->middleware([
        SetTenantContext::class.':required',
        'throttle:30,1',
    ]);

    Route::prefix('activation')
        ->middleware([
            SetTenantContext::class.':required',
            'throttle:portal-activation',
        ])
        ->group(function (): void {
            Route::post('/inspect', [
                PortalInvitationActivationController::class,
                'inspect',
            ]);

            Route::post('/complete', [
                PortalInvitationActivationController::class,
                'activate',
            ]);
        });

    Route::prefix('patient')
        ->middleware([
            SetTenantContext::class.':required',
        ])
        ->group(function (): void {
            Route::post('/login', [
                PatientPortalAuthController::class,
                'login',
            ])->middleware('throttle:portal-login');

            Route::middleware([
                AuthenticatePortalAccessToken::class
                    .':'
                    .PortalAccessToken::ACCOUNT_PATIENT,
            ])->group(function (): void {
                Route::get('/me', [
                    PatientPortalAuthController::class,
                    'me',
                ]);

                Route::post('/logout', [
                    PatientPortalAuthController::class,
                    'logout',
                ]);

                Route::get('/orders', [
                    PatientPortalReadController::class,
                    'orders',
                ]);

                Route::get('/orders/{order}', [
                    PatientPortalReadController::class,
                    'order',
                ]);

                Route::get('/reports', [
                    PatientPortalReadController::class,
                    'reports',
                ]);

                Route::get('/reports/{report}/pdf', [
                    PatientPortalReadController::class,
                    'reportPdf',
                ]);

                Route::get('/reports/{report}', [
                    PatientPortalReadController::class,
                    'report',
                ]);
            });
        });

    Route::prefix('clinician')
        ->middleware([
            SetTenantContext::class.':required',
        ])
        ->group(function (): void {
            Route::post('/login', [
                ClinicianPortalAuthController::class,
                'login',
            ])->middleware('throttle:portal-login');

            Route::middleware([
                AuthenticatePortalAccessToken::class
                    .':'
                    .PortalAccessToken::ACCOUNT_CLINICIAN,
            ])->group(function (): void {
                Route::get('/me', [
                    ClinicianPortalAuthController::class,
                    'me',
                ]);

                Route::post('/logout', [
                    ClinicianPortalAuthController::class,
                    'logout',
                ]);

                Route::get('/patients', [
                    ClinicianPortalReadController::class,
                    'patients',
                ]);

                Route::get('/orders', [
                    ClinicianPortalReadController::class,
                    'orders',
                ]);

                Route::get('/orders/{order}', [
                    ClinicianPortalReadController::class,
                    'order',
                ]);

                Route::get('/reports', [
                    ClinicianPortalReadController::class,
                    'reports',
                ]);

                Route::get('/reports/{report}/pdf', [
                    ClinicianPortalReadController::class,
                    'reportPdf',
                ]);

                Route::get('/reports/{report}', [
                    ClinicianPortalReadController::class,
                    'report',
                ]);
            });
        });
});
