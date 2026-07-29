<?php

use App\Http\Controllers\Api\Tenant\LaboratoryReportController;
use Illuminate\Support\Facades\Route;

Route::post(
    '/laboratory-orders/{laboratoryOrder}/report',
    [
        LaboratoryReportController::class,
        'storeForOrder',
    ]
);

Route::get(
    '/laboratory-orders/{laboratoryOrder}/report',
    [
        LaboratoryReportController::class,
        'showForOrder',
    ]
);

Route::get('/laboratory-reports', [
    LaboratoryReportController::class,
    'index',
]);

Route::get(
    '/laboratory-reports/{laboratoryReport}/pdf',
    [
        LaboratoryReportController::class,
        'downloadPdf',
    ]
);

Route::get(
    '/laboratory-reports/{laboratoryReport}',
    [
        LaboratoryReportController::class,
        'show',
    ]
);
