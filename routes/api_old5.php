<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Landlord\UserController as LandlordUserController;
use App\Http\Controllers\Api\Landlord\TenantController;
use App\Http\Controllers\Api\Landlord\DashboardController;
use App\Http\Controllers\Api\Tenant\UserController as TenantUserController;
use App\Http\Controllers\Api\Tenant\PatientController;
use App\Http\Controllers\Api\Tenant\LabTestController;
use App\Http\Controllers\Api\Tenant\TestResultController;
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC ROUTES
// ============================================
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// ============================================
// LANDLORD ROUTES (Superadmin Only)
// ============================================
Route::middleware(['auth:landlord'])->prefix('landlord')->group(function () {
    // User Management - ALL tenants
    Route::get('/users', [LandlordUserController::class, 'index']);

    // Tenant Management
    Route::get('/tenants', [TenantController::class, 'index']);
    Route::post('/tenants', [TenantController::class, 'store']);
    Route::get('/tenants/{id}', [TenantController::class, 'show']);
    Route::put('/tenants/{id}', [TenantController::class, 'update']);
    Route::delete('/tenants/{id}', [TenantController::class, 'destroy']);
    Route::post('/tenants/{id}/suspend', [TenantController::class, 'suspend']);
    Route::post('/tenants/{id}/activate', [TenantController::class, 'activate']);

    // Global Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
});

// ============================================
// TENANT ROUTES
// ============================================
Route::middleware(['auth:tenant'])->group(function () {
    // User Management - Current tenant only
    Route::get('/users', [TenantUserController::class, 'index']);
    Route::post('/users', [TenantUserController::class, 'store']);
    Route::get('/users/{id}', [TenantUserController::class, 'show']);
    Route::put('/users/{id}', [TenantUserController::class, 'update']);
    Route::delete('/users/{id}', [TenantUserController::class, 'destroy']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/tenant', [AuthController::class, 'tenant']);

    // Patient Management
    Route::get('/patients', [PatientController::class, 'index']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::get('/patients/{id}', [PatientController::class, 'show']);
    Route::put('/patients/{id}', [PatientController::class, 'update']);
    Route::delete('/patients/{id}', [PatientController::class, 'destroy']);

    // Lab Tests Management
    Route::get('/lab-tests', [LabTestController::class, 'index']);
    Route::post('/lab-tests', [LabTestController::class, 'store']);
    Route::get('/lab-tests/{id}', [LabTestController::class, 'show']);
    Route::put('/lab-tests/{id}', [LabTestController::class, 'update']);
    Route::delete('/lab-tests/{id}', [LabTestController::class, 'destroy']);

    // Test Results Management
    Route::get('/test-results', [TestResultController::class, 'index']);
    Route::post('/test-results', [TestResultController::class, 'store']);
    Route::get('/test-results/{id}', [TestResultController::class, 'show']);
    Route::put('/test-results/{id}', [TestResultController::class, 'update']);
    Route::delete('/test-results/{id}', [TestResultController::class, 'destroy']);
});