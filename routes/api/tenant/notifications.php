<?php

use App\Http\Controllers\Api\Tenant\CriticalResultEscalationController;
use App\Http\Controllers\Api\Tenant\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('notifications')->group(function (): void {
    Route::get('/', [
        NotificationController::class,
        'index',
    ]);

    Route::get('/unread-count', [
        NotificationController::class,
        'unreadCount',
    ]);

    Route::patch('/read-all', [
        NotificationController::class,
        'markAllRead',
    ]);

    Route::patch('/{notification}/read', [
        NotificationController::class,
        'markRead',
    ]);

    Route::get('/preferences/current', [
        NotificationController::class,
        'preferences',
    ]);

    Route::put('/preferences/current', [
        NotificationController::class,
        'updatePreference',
    ]);
});

Route::prefix('critical-result-escalations')
    ->group(function (): void {
        Route::get('/', [
            CriticalResultEscalationController::class,
            'index',
        ]);

        Route::post('/{escalation}/acknowledge', [
            CriticalResultEscalationController::class,
            'acknowledge',
        ]);

        Route::post('/{escalation}/resolve', [
            CriticalResultEscalationController::class,
            'resolve',
        ]);
    });
