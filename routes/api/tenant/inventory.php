<?php

use App\Http\Controllers\Api\Tenant\InventoryAdjustmentController;
use App\Http\Controllers\Api\Tenant\InventoryCategoryController;
use App\Http\Controllers\Api\Tenant\InventoryConsumptionController;
use App\Http\Controllers\Api\Tenant\InventoryItemController;
use App\Http\Controllers\Api\Tenant\InventoryReceiptController;
use App\Http\Controllers\Api\Tenant\InventoryStockController;
use App\Http\Controllers\Api\Tenant\InventoryUnitController;
use App\Http\Controllers\Api\Tenant\LabTestInventoryRequirementController;
use App\Http\Controllers\Api\Tenant\SupplierController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventory')->group(function (): void {
    Route::get('/categories', [InventoryCategoryController::class, 'index']);
    Route::post('/categories', [InventoryCategoryController::class, 'store']);
    Route::put('/categories/{category}', [InventoryCategoryController::class, 'update']);

    Route::get('/units', [InventoryUnitController::class, 'index']);
    Route::post('/units', [InventoryUnitController::class, 'store']);
    Route::put('/units/{unit}', [InventoryUnitController::class, 'update']);

    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::put('/suppliers/{supplier}', [SupplierController::class, 'update']);

    Route::get('/items', [InventoryItemController::class, 'index']);
    Route::post('/items', [InventoryItemController::class, 'store']);
    Route::get('/items/{item}', [InventoryItemController::class, 'show']);
    Route::put('/items/{item}', [InventoryItemController::class, 'update']);

    Route::get('/stock', [InventoryStockController::class, 'index']);
    Route::get('/alerts', [InventoryStockController::class, 'alerts']);
    Route::get('/movements', [InventoryStockController::class, 'movements']);

    Route::get('/receipts', [InventoryReceiptController::class, 'index']);
    Route::post('/receipts', [InventoryReceiptController::class, 'store']);
    Route::get('/receipts/{receipt}', [InventoryReceiptController::class, 'show']);

    Route::get('/adjustments', [InventoryAdjustmentController::class, 'index']);
    Route::post('/adjustments', [InventoryAdjustmentController::class, 'store']);
    Route::get('/adjustments/{adjustment}', [InventoryAdjustmentController::class, 'show']);

    Route::get('/consumptions/{consumption}', [InventoryConsumptionController::class, 'show']);
});

Route::get('/lab-tests/{labTest}/inventory-requirements', [LabTestInventoryRequirementController::class, 'show']);
Route::put('/lab-tests/{labTest}/inventory-requirements', [LabTestInventoryRequirementController::class, 'update']);
Route::get('/laboratory-order-items/{laboratoryOrderItem}/inventory-consumptions', [InventoryConsumptionController::class, 'index']);
Route::post('/laboratory-order-items/{laboratoryOrderItem}/inventory-consumptions', [InventoryConsumptionController::class, 'store']);
