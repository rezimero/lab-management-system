<?php

use App\Http\Controllers\Api\Tenant\AnalyzerCredentialController;
use App\Http\Controllers\Api\Tenant\AnalyzerInstrumentController;
use App\Http\Controllers\Api\Tenant\AnalyzerImportController;
use App\Http\Controllers\Api\Tenant\AnalyzerParameterMappingController;
use App\Http\Controllers\Api\Tenant\AnalyzerTestMappingController;
use Illuminate\Support\Facades\Route;

Route::get('/analyzers', [
    AnalyzerInstrumentController::class,
    'index',
]);

Route::post('/analyzers', [
    AnalyzerInstrumentController::class,
    'store',
]);

Route::get('/analyzers/{analyzer}', [
    AnalyzerInstrumentController::class,
    'show',
]);

Route::put('/analyzers/{analyzer}', [
    AnalyzerInstrumentController::class,
    'update',
]);

Route::patch('/analyzers/{analyzer}/deactivate', [
    AnalyzerInstrumentController::class,
    'deactivate',
]);

Route::post('/analyzers/{analyzer}/credentials/rotate', [
    AnalyzerCredentialController::class,
    'rotate',
]);

Route::get('/analyzers/{analyzer}/test-mappings', [
    AnalyzerTestMappingController::class,
    'index',
]);

Route::post('/analyzers/{analyzer}/test-mappings', [
    AnalyzerTestMappingController::class,
    'store',
]);

Route::put(
    '/analyzers/{analyzer}/test-mappings/{testMapping}',
    [
        AnalyzerTestMappingController::class,
        'update',
    ]
);

Route::get(
    '/analyzers/{analyzer}/test-mappings/{testMapping}/parameter-mappings',
    [
        AnalyzerParameterMappingController::class,
        'index',
    ]
);

Route::post(
    '/analyzers/{analyzer}/test-mappings/{testMapping}/parameter-mappings',
    [
        AnalyzerParameterMappingController::class,
        'store',
    ]
);

Route::put(
    '/analyzers/{analyzer}/test-mappings/{testMapping}/parameter-mappings/{parameterMapping}',
    [
        AnalyzerParameterMappingController::class,
        'update',
    ]
);


Route::get('/analyzer-imports', [
    AnalyzerImportController::class,
    'index',
]);

Route::get('/analyzer-imports/{analyzerImport}', [
    AnalyzerImportController::class,
    'show',
]);

Route::post('/analyzer-imports/{analyzerImport}/reprocess', [
    AnalyzerImportController::class,
    'reprocess',
]);

Route::post('/analyzer-imports/{analyzerImport}/reject', [
    AnalyzerImportController::class,
    'reject',
]);
