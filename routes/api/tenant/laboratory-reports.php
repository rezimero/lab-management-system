<?php

use App\Http\Controllers\Api\Tenant\LaboratoryReportAmendmentController;
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

Route::post(
    '/laboratory-reports/{laboratoryReport}/amendments',
    [
        LaboratoryReportAmendmentController::class,
        'store',
    ]
);

Route::get(
    '/laboratory-reports/{laboratoryReport}/amendments',
    [
        LaboratoryReportAmendmentController::class,
        'indexForReport',
    ]
);

Route::get(
    '/laboratory-reports/{laboratoryReport}/lineage',
    [
        LaboratoryReportController::class,
        'lineage',
    ]
);

Route::get(
    '/laboratory-report-amendments/{laboratoryReportAmendment}',
    [
        LaboratoryReportAmendmentController::class,
        'show',
    ]
);

Route::post(
    '/laboratory-report-amendments/{laboratoryReportAmendment}/approve',
    [
        LaboratoryReportAmendmentController::class,
        'approve',
    ]
);

Route::post(
    '/laboratory-report-amendments/{laboratoryReportAmendment}/reject',
    [
        LaboratoryReportAmendmentController::class,
        'reject',
    ]
);

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
