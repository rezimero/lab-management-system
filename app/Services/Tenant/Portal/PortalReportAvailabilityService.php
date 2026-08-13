<?php

namespace App\Services\Tenant\Portal;

use App\Events\Tenant\Portal\LaboratoryReportAvailableForClinician;
use App\Events\Tenant\Portal\LaboratoryReportAvailableForPatient;
use App\Models\ClinicianPortalAccount;
use App\Models\LaboratoryReport;
use App\Models\PatientPortalAccount;

class PortalReportAvailabilityService
{
    public function dispatch(
        int $tenantId,
        LaboratoryReport $report
    ): void {
        $report->loadMissing([
            'laboratoryOrder.patient.portalAccount',
            'laboratoryOrder.referringClinician.portalAccount',
        ]);

        $order = $report->laboratoryOrder;
        $patientAccount = $order
            ?->patient
            ?->portalAccount;

        if (
            $patientAccount instanceof PatientPortalAccount
            && $patientAccount->is_active
            && $patientAccount->activated_at !== null
            && $order->patient?->is_active
        ) {
            LaboratoryReportAvailableForPatient::dispatch(
                $tenantId,
                $report->uuid,
                $order->uuid,
                $patientAccount->uuid
            );
        }

        $clinicianAccount = $order
            ?->referringClinician
            ?->portalAccount;

        if (
            $clinicianAccount instanceof ClinicianPortalAccount
            && $clinicianAccount->is_active
            && $clinicianAccount->activated_at !== null
            && $order->referringClinician?->is_active
        ) {
            LaboratoryReportAvailableForClinician::dispatch(
                $tenantId,
                $report->uuid,
                $order->uuid,
                $clinicianAccount->uuid
            );
        }
    }
}
