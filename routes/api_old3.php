<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\LabTestController;
use App\Http\Controllers\Api\TestResultController;
use App\Http\Controllers\Api\UserController;
use App\Models\Tenant;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\DB;

// ============================================
// Public Routes - No Authentication Required
// ============================================
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

// ============================================
// Landlord Routes - Super Admin Only
// ============================================
Route::middleware(['auth:landlord'])->prefix('landlord')->group(function () {
    Route::get('/tenants', [AuthController::class, 'listTenants']);
    Route::post('/tenants', [AuthController::class, 'createTenant']);
    Route::get('/users', [AuthController::class, 'listLandlordUsers']);
    // Other landlord-only routes
});

// ============================================
// Tenant Routes - All Tenant Users
// ============================================
Route::middleware(['auth:tenant'])->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', [AuthController::class, 'user'])->name('user');
    Route::get('/tenant', [AuthController::class, 'tenant'])->name('tenant');
    
    // Patient Management
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/stats', [PatientController::class, 'stats'])->name('patients.stats');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');
    
    // Lab Tests Management
    Route::get('/lab-tests', [LabTestController::class, 'index'])->name('lab-tests.index');
    Route::get('/lab-tests/categories', [LabTestController::class, 'categories'])->name('lab-tests.categories');
    Route::get('/lab-tests/{labTest}', [LabTestController::class, 'show'])->name('lab-tests.show');
    Route::post('/lab-tests', [LabTestController::class, 'store'])->name('lab-tests.store');
    Route::put('/lab-tests/{labTest}', [LabTestController::class, 'update'])->name('lab-tests.update');
    Route::delete('/lab-tests/{labTest}', [LabTestController::class, 'destroy'])->name('lab-tests.destroy');
    
    // Test Results Management
    Route::get('/test-results', [TestResultController::class, 'index'])->name('test-results.index');
    Route::get('/test-results/stats', [TestResultController::class, 'stats'])->name('test-results.stats');
    Route::get('/test-results/patient/{patientId}', [TestResultController::class, 'getByPatient'])->name('test-results.by-patient');
    Route::get('/test-results/{testResult}', [TestResultController::class, 'show'])->name('test-results.show');
    Route::post('/test-results', [TestResultController::class, 'store'])->name('test-results.store');
    Route::put('/test-results/{testResult}', [TestResultController::class, 'update'])->name('test-results.update');
    Route::delete('/test-results/{testResult}', [TestResultController::class, 'destroy'])->name('test-results.destroy');
    
    // User Management - Tenant users only
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole'])->name('users.assign-role');
    Route::get('/roles', [UserController::class, 'getRoles'])->name('roles.index');

    // Debug route to pinpoint database context leaks
    Route::get('/debug/connection', function (\Illuminate\Http\Request $request) {
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
            'guard' => auth()->getDefaultDriver(),
        ]);
    });
});