<?php

use App\Http\Controllers\Api\Landlord\DashboardController;
use App\Http\Controllers\Api\Landlord\ImpersonationController;
use App\Http\Controllers\Api\Landlord\LabTestController as LandlordLabTestController;
use App\Http\Controllers\Api\Landlord\PatientController as LandlordPatientController;
use App\Http\Controllers\Api\Landlord\TenantController;
use App\Http\Controllers\Api\Landlord\TestResultController as LandlordTestResultController;
use App\Http\Controllers\Api\Landlord\UserController as LandlordUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'superadmin'])
    ->prefix('landlord')
    ->group(function (): void {

        // ============================================
        // DASHBOARD
        // ============================================

        Route::get('/dashboard/stats', [
            DashboardController::class,
            'stats',
        ]);

        Route::get('/dashboard/overview', [
            DashboardController::class,
            'overview',
        ]);

        Route::get('/dashboard/activity', [
            DashboardController::class,
            'recentActivity',
        ]);

        // ============================================
        // TENANTS
        // ============================================

        Route::get('/tenants', [
            TenantController::class,
            'index',
        ]);

        Route::post('/tenants', [
            TenantController::class,
            'store',
        ]);

        Route::get('/tenants/{id}', [
            TenantController::class,
            'show',
        ]);

        Route::put('/tenants/{id}', [
            TenantController::class,
            'update',
        ]);

        Route::delete('/tenants/{id}', [
            TenantController::class,
            'destroy',
        ]);

        Route::post('/tenants/{id}/suspend', [
            TenantController::class,
            'suspend',
        ]);

        Route::post('/tenants/{id}/activate', [
            TenantController::class,
            'activate',
        ]);

        Route::post('/tenants/{id}/reset-admin-password', [
            TenantController::class,
            'resetAdminPassword',
        ]);

        Route::get('/tenants/{id}/stats', [
            TenantController::class,
            'tenantStats',
        ]);

        // ============================================
        // USERS AND ROLES
        // ============================================

        Route::get('/users', [
            LandlordUserController::class,
            'index',
        ]);

        Route::get('/users/{id}', [
            LandlordUserController::class,
            'show',
        ]);

        Route::post('/users', [
            LandlordUserController::class,
            'store',
        ]);

        Route::put('/users/{id}', [
            LandlordUserController::class,
            'update',
        ]);

        Route::delete('/users/{id}', [
            LandlordUserController::class,
            'destroy',
        ]);

        Route::post('/users/{id}/reset-password', [
            LandlordUserController::class,
            'resetPassword',
        ]);

        Route::post('/users/{id}/assign-role', [
            LandlordUserController::class,
            'assignRole',
        ]);

        Route::get('/roles', [
            LandlordUserController::class,
            'getRoles',
        ]);

        // ============================================
        // PATIENTS
        // ============================================

        Route::get('/patients', [
            LandlordPatientController::class,
            'index',
        ]);

        Route::get('/patients/{id}', [
            LandlordPatientController::class,
            'show',
        ]);

        Route::delete('/patients/{id}', [
            LandlordPatientController::class,
            'destroy',
        ]);

        // ============================================
        // LAB TESTS
        // ============================================

        Route::get('/lab-tests', [
            LandlordLabTestController::class,
            'index',
        ]);

        Route::get('/lab-tests/{id}', [
            LandlordLabTestController::class,
            'show',
        ]);

        Route::delete('/lab-tests/{id}', [
            LandlordLabTestController::class,
            'destroy',
        ]);

        // ============================================
        // TEST RESULTS
        // ============================================

        Route::get('/test-results', [
            LandlordTestResultController::class,
            'index',
        ]);

        Route::get('/test-results/{id}', [
            LandlordTestResultController::class,
            'show',
        ]);

        Route::delete('/test-results/{id}', [
            LandlordTestResultController::class,
            'destroy',
        ]);

        // ============================================
        // IMPERSONATION
        // ============================================

        Route::post('/impersonate/stop', [
            ImpersonationController::class,
            'stopImpersonating',
        ]);

        Route::get('/impersonate/status', [
            ImpersonationController::class,
            'status',
        ]);

        Route::post('/impersonate/{tenantId}', [
            ImpersonationController::class,
            'impersonate',
        ]);
    });