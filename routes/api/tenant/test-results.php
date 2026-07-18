<?php

use App\Http\Controllers\Api\Tenant\LaboratoryOrderItemProcessingController;
use App\Http\Controllers\Api\Tenant\TestResultController;
use App\Http\Controllers\Api\Tenant\TestResultWorkflowController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/laboratory-order-items/{laboratoryOrderItem}/start',
    [
        LaboratoryOrderItemProcessingController::class,
        'store',
    ]
);

Route::post(
    '/laboratory-order-items/{laboratoryOrderItem}/test-result',
    [
        TestResultController::class,
        'store',
    ]
);

Route::get('/test-results', [
    TestResultController::class,
    'index',
]);

Route::get('/test-results/stats', [
    TestResultController::class,
    'stats',
]);

Route::get('/test-results/{testResult}', [
    TestResultController::class,
    'show',
]);

Route::put('/test-results/{testResult}', [
    TestResultController::class,
    'update',
]);

Route::post(
    '/test-results/{testResult}/submit',
    [
        TestResultWorkflowController::class,
        'submit',
    ]
);

Route::post(
    '/test-results/{testResult}/approve',
    [
        TestResultWorkflowController::class,
        'approve',
    ]
);

Route::post(
    '/test-results/{testResult}/reject',
    [
        TestResultWorkflowController::class,
        'reject',
    ]
);
