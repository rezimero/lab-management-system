<?php

use App\Http\Controllers\Api\LabTestController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\Tenant\BranchController;
use App\Http\Controllers\Api\Tenant\CorporateAccountController;
use App\Http\Controllers\Api\Tenant\DepartmentController;
use App\Http\Controllers\Api\Tenant\EntitlementController;
use App\Http\Controllers\Api\Tenant\InvoiceController;
use App\Http\Controllers\Api\Tenant\LaboratoryOrderController;
use App\Http\Controllers\Api\Tenant\PaymentController;
use App\Http\Controllers\Api\Tenant\PriceListController;
use App\Http\Controllers\Api\Tenant\SpecimenCollectionController;
use App\Http\Controllers\Api\Tenant\SpecimenController;
use App\Http\Controllers\Api\Tenant\SpecimenReplacementController;
use App\Http\Controllers\Api\Tenant\SpecimenStatusController;
use App\Http\Controllers\Api\Tenant\TenantProfileController;
use App\Http\Controllers\Api\UserController;
use App\Http\Middleware\SetTenantContext;
use Illuminate\Support\Facades\Route;

Route::middleware([
    SetTenantContext::class.':required',
    'auth:sanctum',
])->group(function (): void {

    // ============================================
    // TENANT PROFILE
    // ============================================

    Route::get('/tenant/profile', [
        TenantProfileController::class,
        'show',
    ]);

    Route::put('/tenant/profile', [
        TenantProfileController::class,
        'update',
    ]);

    Route::post('/tenant/profile/logo', [
        TenantProfileController::class,
        'uploadLogo',
    ]);

    Route::post('/tenant/profile/favicon', [
        TenantProfileController::class,
        'uploadFavicon',
    ]);

    Route::post(
        '/tenant/profile/director-signature',
        [
            TenantProfileController::class,
            'uploadDirectorSignature',
        ]
    );

    // ============================================
    // SUBSCRIPTION ENTITLEMENTS
    // ============================================

    Route::get('/tenant/entitlements', EntitlementController::class);

    // ============================================
    // BRANCHES AND COLLECTION CENTRES
    // ============================================

    Route::get('/branches', [BranchController::class, 'index']);
    Route::post('/branches', [BranchController::class, 'store']);
    Route::get('/branches/{branch}', [BranchController::class, 'show']);
    Route::put('/branches/{branch}', [BranchController::class, 'update']);
    Route::put('/branches/{branch}/users', [BranchController::class, 'assignUsers']);
    Route::patch('/branches/{branch}/deactivate', [BranchController::class, 'deactivate']);

    // ============================================
    // BILLING, INVOICES, PAYMENTS AND RECEIPTS
    // ============================================

    Route::get('/price-lists', [PriceListController::class, 'index']);
    Route::post('/price-lists', [PriceListController::class, 'store']);
    Route::put('/price-lists/{priceList}', [PriceListController::class, 'update']);

    Route::get('/corporate-accounts', [CorporateAccountController::class, 'index']);
    Route::post('/corporate-accounts', [CorporateAccountController::class, 'store']);
    Route::put('/corporate-accounts/{corporateAccount}', [CorporateAccountController::class, 'update']);

    Route::get('/invoices', [InvoiceController::class, 'index']);
    Route::post('/invoices', [InvoiceController::class, 'store']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::post('/invoices/{invoice}/issue', [InvoiceController::class, 'issue']);
    Route::post('/invoices/{invoice}/void', [InvoiceController::class, 'void']);
    Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store']);
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt']);

    // ============================================
    // DEPARTMENTS
    // ============================================

    Route::apiResource(
        'departments',
        DepartmentController::class
    );

    Route::patch(
        '/departments/{department}/activate',
        [
            DepartmentController::class,
            'activate',
        ]
    );

    Route::patch(
        '/departments/{department}/deactivate',
        [
            DepartmentController::class,
            'deactivate',
        ]
    );

    // ============================================
    // PATIENTS
    // ============================================

    Route::get('/patients', [
        PatientController::class,
        'index',
    ]);

    Route::get('/patients/stats', [
        PatientController::class,
        'stats',
    ]);

    Route::get('/patients/{patient}', [
        PatientController::class,
        'show',
    ]);

    Route::post('/patients', [
        PatientController::class,
        'store',
    ]);

    Route::put('/patients/{patient}', [
        PatientController::class,
        'update',
    ]);

    Route::delete('/patients/{patient}', [
        PatientController::class,
        'destroy',
    ]);

    // ============================================
    // LAB TESTS
    // ============================================

    Route::get('/lab-tests', [
        LabTestController::class,
        'index',
    ]);

    Route::get('/lab-tests/categories', [
        LabTestController::class,
        'categories',
    ]);

    Route::get('/lab-tests/{labTest}', [
        LabTestController::class,
        'show',
    ]);

    Route::post('/lab-tests', [
        LabTestController::class,
        'store',
    ]);

    Route::put('/lab-tests/{labTest}', [
        LabTestController::class,
        'update',
    ]);

    Route::delete('/lab-tests/{labTest}', [
        LabTestController::class,
        'destroy',
    ]);

    // ============================================
    // INVENTORY AND REAGENTS
    // ============================================

    require __DIR__.'/tenant/inventory.php';

    // ============================================
    // REFERRAL LABORATORIES
    // ============================================

    require __DIR__.'/tenant/referrals.php';

    // ============================================
    // PORTAL ACCESS / REFERRING CLINICIANS
    // ============================================

    require __DIR__.'/tenant/portal-access.php';

    // ============================================
    // NOTIFICATIONS / CRITICAL-RESULT ESCALATION
    // ============================================

    require __DIR__.'/tenant/notifications.php';

    // ============================================
    // ANALYZER INTEGRATION
    // ============================================

    require __DIR__.'/tenant/analyzers.php';

    // ============================================
    // LABORATORY ORDERS
    // ============================================

    Route::get('/laboratory-orders', [
        LaboratoryOrderController::class,
        'index',
    ]);

    Route::post('/laboratory-orders', [
        LaboratoryOrderController::class,
        'store',
    ]);

    Route::post(
        '/laboratory-orders/{laboratoryOrder}/specimens',
        [
            SpecimenCollectionController::class,
            'store',
        ]
    );

    Route::patch(
        '/laboratory-orders/{laboratoryOrder}/status',
        [
            LaboratoryOrderController::class,
            'updateStatus',
        ]
    );

    Route::get(
        '/laboratory-orders/{laboratoryOrder}',
        [
            LaboratoryOrderController::class,
            'show',
        ]
    );

    // ============================================
    // SPECIMENS
    // ============================================

    Route::get('/specimens', [
        SpecimenController::class,
        'index',
    ]);

    Route::get('/specimens/stats', [
        SpecimenController::class,
        'stats',
    ]);

    Route::post(
        '/specimens/{specimen}/replacement',
        [
            SpecimenReplacementController::class,
            'store',
        ]
    );

    Route::patch(
        '/specimens/{specimen}/status',
        [
            SpecimenStatusController::class,
            'update',
        ]
    );

    Route::get('/specimens/{specimen}', [
        SpecimenController::class,
        'show',
    ]);

    // ============================================
    // TEST PROCESSING AND RESULTS
    // ============================================

    require __DIR__.'/tenant/test-results.php';

    // ============================================
    // LABORATORY REPORTS
    // ============================================

    require __DIR__
        .'/tenant/laboratory-reports.php';

    // ============================================
    // USERS AND ROLES
    // ============================================

    Route::get('/users', [
        UserController::class,
        'index',
    ]);

    Route::get('/users/{user}', [
        UserController::class,
        'show',
    ]);

    Route::post('/users', [
        UserController::class,
        'store',
    ]);

    Route::put('/users/{user}', [
        UserController::class,
        'update',
    ]);

    Route::delete('/users/{user}', [
        UserController::class,
        'destroy',
    ]);

    Route::post('/users/{user}/assign-role', [
        UserController::class,
        'assignRole',
    ]);

    Route::get('/roles', [
        UserController::class,
        'getRoles',
    ]);
});
