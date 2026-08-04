<?php

use App\Http\Controllers\Api\Tenant\ReferralLaboratoryController;
use App\Http\Controllers\Api\Tenant\ReferralLaboratoryTestController;
use Illuminate\Support\Facades\Route;

Route::prefix('referrals')->group(function (): void {
    Route::get(
        '/laboratories',
        [ReferralLaboratoryController::class, 'index']
    );
    Route::post(
        '/laboratories',
        [ReferralLaboratoryController::class, 'store']
    );
    Route::get(
        '/laboratories/{laboratory}',
        [ReferralLaboratoryController::class, 'show']
    );
    Route::put(
        '/laboratories/{laboratory}',
        [ReferralLaboratoryController::class, 'update']
    );

    Route::get(
        '/laboratories/{laboratory}/tests',
        [ReferralLaboratoryTestController::class, 'index']
    );
    Route::post(
        '/laboratories/{laboratory}/tests',
        [ReferralLaboratoryTestController::class, 'store']
    );
    Route::put(
        '/laboratory-tests/{mapping}',
        [ReferralLaboratoryTestController::class, 'update']
    );
});
