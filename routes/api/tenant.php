<?php

use App\Http\Controllers\Api\LabTestController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\Tenant\DepartmentController;
use App\Http\Controllers\Api\Tenant\LaboratoryOrderController;
use App\Http\Controllers\Api\Tenant\TenantProfileController;
use App\Http\Controllers\Api\TestResultController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Support\Facades\Route;

Route::middleware([
    SetTenantContext::class.':required',
    'auth:sanctum',
])->group(function (): void {

    // ============================================
    // TENANT PROFILE
    // ============================================

    Route::get('/tenant/profile', [
        TenantProfileController::class,
        'show',
    ]);

    Route::put('/tenant/profile', [
        TenantProfileController::class,
        'update',
    ]);

    Route::post('/tenant/profile/logo', [
        TenantProfileController::class,
        'uploadLogo',
    ]);

    Route::post('/tenant/profile/favicon', [
        TenantProfileController::class,
        'uploadFavicon',
    ]);

    Route::post('/tenant/profile/director-signature', [
        TenantProfileController::class,
        'uploadDirectorSignature',
    ]);

    // ============================================
    // DEPARTMENTS
    // ============================================

    Route::apiResource(
        'departments',
        DepartmentController::class
    );

    Route::patch('/departments/{department}/activate', [
        DepartmentController::class,
        'activate',
    ]);

    Route::patch('/departments/{department}/deactivate', [
        DepartmentController::class,
        'deactivate',
    ]);

    // ============================================
    // PATIENTS
    // ============================================

    Route::get('/patients', [
        PatientController::class,
        'index',
    ]);

    Route::get('/patients/stats', [
        PatientController::class,
        'stats',
    ]);

    Route::get('/patients/{patient}', [
        PatientController::class,
        'show',
    ]);

    Route::post('/patients', [
        PatientController::class,
        'store',
    ]);

    Route::put('/patients/{patient}', [
        PatientController::class,
        'update',
    ]);

    Route::delete('/patients/{patient}', [
        PatientController::class,
        'destroy',
    ]);

    // ============================================
    // LAB TESTS
    // ============================================

    Route::get('/lab-tests', [
        LabTestController::class,
        'index',
    ]);

    Route::get('/lab-tests/categories', [
        LabTestController::class,
        'categories',
    ]);

    Route::get('/lab-tests/{labTest}', [
        LabTestController::class,
        'show',
    ]);

    Route::post('/lab-tests', [
        LabTestController::class,
        'store',
    ]);

    Route::put('/lab-tests/{labTest}', [
        LabTestController::class,
        'update',
    ]);

    Route::delete('/lab-tests/{labTest}', [
        LabTestController::class,
        'destroy',
    ]);

    // ============================================
    // LABORATORY ORDERS
    // ============================================

    Route::get('/laboratory-orders', [
        LaboratoryOrderController::class,
        'index',
    ]);

    Route::post('/laboratory-orders', [
        LaboratoryOrderController::class,
        'store',
    ]);

    Route::get('/laboratory-orders/{laboratoryOrder}', [
        LaboratoryOrderController::class,
        'show',
    ]);

    // ============================================
    // TEST RESULTS
    // ============================================

    Route::get('/test-results', [
        TestResultController::class,
        'index',
    ]);

    Route::get('/test-results/stats', [
        TestResultController::class,
        'stats',
    ]);

    Route::get('/test-results/patient/{patientId}', [
        TestResultController::class,
        'getByPatient',
    ]);

    Route::get('/test-results/{testResult}', [
        TestResultController::class,
        'show',
    ]);

    Route::post('/test-results', [
        TestResultController::class,
        'store',
    ]);

    Route::put('/test-results/{testResult}', [
        TestResultController::class,
        'update',
    ]);

    Route::delete('/test-results/{testResult}', [
        TestResultController::class,
        'destroy',
    ]);

    // ============================================
    // USERS AND ROLES
    // ============================================

    Route::get('/users', [
        UserController::class,
        'index',
    ]);

    Route::get('/users/{user}', [
        UserController::class,
        'show',
    ]);

    Route::post('/users', [
        UserController::class,
        'store',
    ]);

    Route::put('/users/{user}', [
        UserController::class,
        'update',
    ]);

    Route::delete('/users/{user}', [
        UserController::class,
        'destroy',
    ]);

    Route::post('/users/{user}/assign-role', [
        UserController::class,
        'assignRole',
    ]);

    Route::get('/roles', [
        UserController::class,
        'getRoles',
    ]);
});
