<?php

use App\Http\Controllers\Api\Tenant\PortalAccessAdministrationController;
use App\Http\Controllers\Api\Tenant\ReferringClinicianController;
use Illuminate\Support\Facades\Route;

Route::get('/referring-clinicians', [
    ReferringClinicianController::class,
    'index',
]);

Route::post('/referring-clinicians', [
    ReferringClinicianController::class,
    'store',
]);

Route::put(
    '/referring-clinicians/{referringClinician}',
    [
        ReferringClinicianController::class,
        'update',
    ]
);

Route::patch(
    '/referring-clinicians/{referringClinician}/activate',
    [
        ReferringClinicianController::class,
        'activate',
    ]
);

Route::prefix('portal-access')->group(function (): void {
    Route::get('/patients', [
        PortalAccessAdministrationController::class,
        'patients',
    ]);

    Route::post('/patients/{patient}/invitations', [
        PortalAccessAdministrationController::class,
        'invitePatient',
    ]);

    Route::get('/clinicians', [
        PortalAccessAdministrationController::class,
        'clinicians',
    ]);

    Route::post(
        '/clinicians/{referringClinician}/invitations',
        [
            PortalAccessAdministrationController::class,
            'inviteClinician',
        ]
    );

    Route::patch(
        '/patient-accounts/{account}/activate',
        [
            PortalAccessAdministrationController::class,
            'activatePatientAccount',
        ]
    );

    Route::patch(
        '/patient-accounts/{account}/deactivate',
        [
            PortalAccessAdministrationController::class,
            'deactivatePatientAccount',
        ]
    );

    Route::patch(
        '/clinician-accounts/{account}/activate',
        [
            PortalAccessAdministrationController::class,
            'activateClinicianAccount',
        ]
    );

    Route::patch(
        '/clinician-accounts/{account}/deactivate',
        [
            PortalAccessAdministrationController::class,
            'deactivateClinicianAccount',
        ]
    );

    Route::delete('/invitations/{invitation}', [
        PortalAccessAdministrationController::class,
        'revokeInvitation',
    ]);
});

Route::patch(
    '/referring-clinicians/{referringClinician}/deactivate',
    [
        ReferringClinicianController::class,
        'deactivate',
    ]
);
