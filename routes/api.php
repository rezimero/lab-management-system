<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\LabTestController;
use App\Http\Controllers\Api\TestResultController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\Landlord\DashboardController;
use App\Http\Controllers\Api\Landlord\TenantController;
use App\Http\Controllers\Api\Landlord\UserController as LandlordUserController;
use App\Http\Controllers\Api\Landlord\PatientController as LandlordPatientController;
use App\Http\Controllers\Api\Landlord\LabTestController as LandlordLabTestController;
use App\Http\Controllers\Api\Landlord\TestResultController as LandlordTestResultController;
use App\Http\Controllers\Api\Landlord\ImpersonationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

// ============================================
// PUBLIC ROUTES - No Authentication Required
// ============================================
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

// ============================================
// LANDLORD ROUTES - Superadmin (God Mode)
// ============================================
Route::middleware(['auth:sanctum', 'superadmin'])->prefix('landlord')->group(function () {
    
    // ============================================
    // DASHBOARD - Global Analytics
    // ============================================
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
    Route::get('/dashboard/activity', [DashboardController::class, 'recentActivity']);
    
    // ============================================
    // TENANT MANAGEMENT
    // ============================================
    Route::get('/tenants', [TenantController::class, 'index']);
    Route::post('/tenants', [TenantController::class, 'store']);
    Route::get('/tenants/{id}', [TenantController::class, 'show']);
    Route::put('/tenants/{id}', [TenantController::class, 'update']);
    Route::delete('/tenants/{id}', [TenantController::class, 'destroy']);
    Route::post('/tenants/{id}/suspend', [TenantController::class, 'suspend']);
    Route::post('/tenants/{id}/activate', [TenantController::class, 'activate']);
    Route::post('/tenants/{id}/reset-admin-password', [TenantController::class, 'resetAdminPassword']);
    Route::get('/tenants/{id}/stats', [TenantController::class, 'tenantStats']);
    
    // ============================================
    // GLOBAL USER MANAGEMENT (God Mode)
    // ============================================
    Route::get('/users', [LandlordUserController::class, 'index']);
    Route::get('/users/{id}', [LandlordUserController::class, 'show']);
    Route::post('/users', [LandlordUserController::class, 'store']);
    Route::put('/users/{id}', [LandlordUserController::class, 'update']);
    Route::delete('/users/{id}', [LandlordUserController::class, 'destroy']);
    Route::post('/users/{id}/reset-password', [LandlordUserController::class, 'resetPassword']);
    Route::post('/users/{id}/assign-role', [LandlordUserController::class, 'assignRole']);
    Route::get('/roles', [LandlordUserController::class, 'getRoles']);
    
    // ============================================
    // GLOBAL PATIENT MANAGEMENT
    // ============================================
    Route::get('/patients', [LandlordPatientController::class, 'index']);
    Route::get('/patients/{id}', [LandlordPatientController::class, 'show']);
    Route::delete('/patients/{id}', [LandlordPatientController::class, 'destroy']);
    
    // ============================================
    // GLOBAL LAB TEST MANAGEMENT
    // ============================================
    Route::get('/lab-tests', [LandlordLabTestController::class, 'index']);
    Route::get('/lab-tests/{id}', [LandlordLabTestController::class, 'show']);
    Route::delete('/lab-tests/{id}', [LandlordLabTestController::class, 'destroy']);
    
    // ============================================
    // GLOBAL TEST RESULT MANAGEMENT
    // ============================================
    Route::get('/test-results', [LandlordTestResultController::class, 'index']);
    Route::get('/test-results/{id}', [LandlordTestResultController::class, 'show']);
    Route::delete('/test-results/{id}', [LandlordTestResultController::class, 'destroy']);
    
    // ============================================
    // IMPERSONATION (Enter any tenant)
    // ============================================
    Route::post('/impersonate/{tenantId}', [ImpersonationController::class, 'impersonate']);
    Route::post('/impersonate/stop', [ImpersonationController::class, 'stopImpersonating']);
    Route::get('/impersonate/status', [ImpersonationController::class, 'status']);
});

// ============================================
// TENANT ROUTES - Regular Users
// ============================================
Route::middleware(['auth:sanctum'])->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/tenant', [AuthController::class, 'tenant']);
    
    // Patient Management
    Route::get('/patients', [PatientController::class, 'index']);
    Route::get('/patients/stats', [PatientController::class, 'stats']);
    Route::get('/patients/{patient}', [PatientController::class, 'show']);
    Route::post('/patients', [PatientController::class, 'store']);
    Route::put('/patients/{patient}', [PatientController::class, 'update']);
    Route::delete('/patients/{patient}', [PatientController::class, 'destroy']);
    
    // Lab Tests Management
    Route::get('/lab-tests', [LabTestController::class, 'index']);
    Route::get('/lab-tests/categories', [LabTestController::class, 'categories']);
    Route::get('/lab-tests/{labTest}', [LabTestController::class, 'show']);
    Route::post('/lab-tests', [LabTestController::class, 'store']);
    Route::put('/lab-tests/{labTest}', [LabTestController::class, 'update']);
    Route::delete('/lab-tests/{labTest}', [LabTestController::class, 'destroy']);
    
    // Test Results Management
    Route::get('/test-results', [TestResultController::class, 'index']);
    Route::get('/test-results/stats', [TestResultController::class, 'stats']);
    Route::get('/test-results/patient/{patientId}', [TestResultController::class, 'getByPatient']);
    Route::get('/test-results/{testResult}', [TestResultController::class, 'show']);
    Route::post('/test-results', [TestResultController::class, 'store']);
    Route::put('/test-results/{testResult}', [TestResultController::class, 'update']);
    Route::delete('/test-results/{testResult}', [TestResultController::class, 'destroy']);
    
    // User Management - Tenant users only
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
    Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole']);
    Route::get('/roles', [UserController::class, 'getRoles']);
});

// ============================================
// DEBUG ROUTES
// ============================================
Route::get('/debug/connection', function (Request $request) {
    $tenant = Tenant::current();
    $user = $request->user();
    
    return response()->json([
        'tenant' => $tenant ? [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'domain' => $tenant->domain,
            'database' => $tenant->database,
        ] : null,
        'current_database' => DB::connection()->getDatabaseName(),
        'user' => $user ? [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ] : null,
        'is_superadmin_override' => app()->bound('superadmin.override') ? app('superadmin.override') : false,
    ]);
})->middleware(['auth:sanctum']);