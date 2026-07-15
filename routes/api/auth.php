<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Support\Facades\Route;

// ============================================
// PUBLIC AUTHENTICATION ROUTES
// ============================================

Route::post('/login', [
    AuthController::class,
    'login',
])->name('login');

Route::post('/register', [
    AuthController::class,
    'register',
])->name('register');

// ============================================
// AUTHENTICATED ACCOUNT CONTEXT ROUTES
// ============================================

Route::middleware([
    SetTenantContext::class,
    'auth:sanctum',
])->group(function (): void {
    Route::post('/logout', [
        AuthController::class,
        'logout',
    ]);

    Route::get('/user', [
        AuthController::class,
        'user',
    ]);

    Route::get('/tenant', [
        AuthController::class,
        'tenant',
    ]);
});