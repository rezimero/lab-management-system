<?php

use App\Http\Controllers\Api\AnalyzerMachineIngestController;
use App\Http\Middleware\AuthenticateAnalyzerMachine;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Support\Facades\Route;

Route::middleware([
    SetTenantContext::class.':required',
    AuthenticateAnalyzerMachine::class,
    'throttle:120,1',
])->prefix('analyzer-machine')->group(function (): void {
    Route::post('/messages', [
        AnalyzerMachineIngestController::class,
        'store',
    ]);
});
