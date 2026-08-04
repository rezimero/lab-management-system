<?php

use App\Http\Controllers\Api\Tenant\LaboratoryOrderItemReferralController;
use App\Http\Controllers\Api\Tenant\LaboratoryReferralController;
use App\Http\Controllers\Api\Tenant\LaboratoryReferralResultController;
use App\Http\Controllers\Api\Tenant\LaboratoryReferralWorkflowController;
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

    Route::get('/', [LaboratoryReferralController::class, 'index']);
    Route::get(
        '/summary',
        [LaboratoryReferralController::class, 'summary']
    );
    Route::post('/', [LaboratoryReferralController::class, 'store']);
    Route::get(
        '/{referral}',
        [LaboratoryReferralController::class, 'show']
    );

    Route::post(
        '/{referral}/dispatch',
        [LaboratoryReferralWorkflowController::class, 'dispatch']
    );
    Route::post(
        '/{referral}/acknowledge',
        [LaboratoryReferralWorkflowController::class, 'acknowledge']
    );
    Route::post(
        '/{referral}/reject',
        [LaboratoryReferralWorkflowController::class, 'reject']
    );
    Route::post(
        '/{referral}/cancel',
        [LaboratoryReferralWorkflowController::class, 'cancel']
    );
    Route::post(
        '/{referral}/results',
        [LaboratoryReferralResultController::class, 'store']
    );
});

Route::get(
    '/laboratory-order-items/{laboratoryOrderItem}/referrals',
    [LaboratoryOrderItemReferralController::class, 'index']
);
