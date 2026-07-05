<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\LabTestController;
use App\Http\Controllers\Api\TestResultController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

// ============================================
// Public Routes - No Authentication Required
// ============================================
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');

// ============================================
// Debug Routes - Temporary (Remove in production)
// ============================================
Route::get('/debug/tenant', function (Request $request) {
    $tenant = Tenant::current();
    return response()->json([
        'tenant' => $tenant ? [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'domain' => $tenant->domain,
            'database' => $tenant->database,
        ] : null,
        'database_name' => DB::connection()->getDatabaseName(),
        'user' => $request->user() ? $request->user()->id : null,
    ]);
})->middleware('auth:sanctum');

Route::get('/debug/check-db', function () {
    $tenant = Tenant::first();
    if ($tenant) {
        config(['database.connections.tenant.database' => $tenant->database]);
        DB::purge('tenant');
        DB::connection('tenant')->reconnect();
        $tenant->makeCurrent();
        return response()->json([
            'database' => DB::connection()->getDatabaseName(),
            'tenant' => $tenant->name,
        ]);
    }
    return response()->json(['error' => 'No tenant found']);
});

// ============================================
// Protected Routes - Authentication Required
// ============================================
Route::middleware(['auth:sanctum'])->group(function () {
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', [AuthController::class, 'user'])->name('user');
    Route::get('/tenant', [AuthController::class, 'tenant'])->name('tenant');
    
    // ============================================
    // Patient Management
    // ============================================
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/stats', [PatientController::class, 'stats'])->name('patients.stats');
    Route::get('/patients/{patient}', [PatientController::class, 'show'])->name('patients.show');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
    Route::put('/patients/{patient}', [PatientController::class, 'update'])->name('patients.update');
    Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->name('patients.destroy');
    
    // ============================================
    // Lab Tests Management
    // ============================================
    Route::get('/lab-tests', [LabTestController::class, 'index'])->name('lab-tests.index');
    Route::get('/lab-tests/categories', [LabTestController::class, 'categories'])->name('lab-tests.categories');
    Route::get('/lab-tests/{labTest}', [LabTestController::class, 'show'])->name('lab-tests.show');
    Route::post('/lab-tests', [LabTestController::class, 'store'])->name('lab-tests.store');
    Route::put('/lab-tests/{labTest}', [LabTestController::class, 'update'])->name('lab-tests.update');
    Route::delete('/lab-tests/{labTest}', [LabTestController::class, 'destroy'])->name('lab-tests.destroy');
    
    // ============================================
    // Test Results Management
    // ============================================
    Route::get('/test-results', [TestResultController::class, 'index'])->name('test-results.index');
    Route::get('/test-results/stats', [TestResultController::class, 'stats'])->name('test-results.stats');
    Route::get('/test-results/patient/{patientId}', [TestResultController::class, 'getByPatient'])->name('test-results.by-patient');
    Route::get('/test-results/{testResult}', [TestResultController::class, 'show'])->name('test-results.show');
    Route::post('/test-results', [TestResultController::class, 'store'])->name('test-results.store');
    Route::put('/test-results/{testResult}', [TestResultController::class, 'update'])->name('test-results.update');
    Route::delete('/test-results/{testResult}', [TestResultController::class, 'destroy'])->name('test-results.destroy');
    
    // ============================================
    // User Management
    // ============================================
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole'])->name('users.assign-role');
    Route::get('/roles', [UserController::class, 'getRoles'])->name('roles.index');

    // ============================================
    // Debug Routes - Tenant Connection Check
    // ============================================
    Route::get('/debug/connection', function (Request $request) {
        $tenant = Tenant::current();
        $dbName = DB::connection()->getDatabaseName();
        $user = $request->user();
        
        return response()->json([
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'database' => $tenant->database,
            ] : null,
            'current_database' => $dbName,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ] : null,
            'users_in_tenant' => $tenant ? \App\Models\User::all()->count() : 0,
            'all_users' => $tenant ? \App\Models\User::all()->pluck('email')->toArray() : [],
            'total_tenants' => \App\Models\Tenant::all()->count(),
        ]);
    })->middleware(['auth:sanctum', 'tenant']);
});