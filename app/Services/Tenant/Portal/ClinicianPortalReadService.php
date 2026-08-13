<?php

namespace App\Services\Tenant\Portal;

use App\Models\ClinicianPortalAccount;
use App\Models\LaboratoryOrder;
use App\Models\LaboratoryReport;
use App\Models\Patient;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ClinicianPortalReadService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginatePatients(
        ClinicianPortalAccount $account,
        array $filters
    ): LengthAwarePaginator {
        $clinicianId = (
            $account->referring_clinician_id
        );

        $patientIds = LaboratoryOrder::query()
            ->select('patient_id')
            ->where(
                'referring_clinician_id',
                $clinicianId
            )
            ->whereNotNull('patient_id');

        $query = Patient::query()
            ->whereIn('id', $patientIds);

        $search = $filters['search'] ?? null;

        if (
            is_string($search)
            && $search !== ''
        ) {
            $query->where(
                function ($query) use ($search): void {
                    $term = '%'.$search.'%';

                    $query
                        ->where(
                            'medical_record_number',
                            'like',
                            $term
                        )
                        ->orWhere(
                            'first_name',
                            'like',
                            $term
                        )
                        ->orWhere(
                            'last_name',
                            'like',
                            $term
                        )
                        ->orWhereRaw(
                            "CONCAT(first_name, ' ', last_name) LIKE ?",
                            [$term]
                        );
                }
            );
        }

        return $query
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->orderBy('id')
            ->paginate(
                (int) ($filters['per_page'] ?? 25)
            );
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateOrders(
        ClinicianPortalAccount $account,
        array $filters
    ): LengthAwarePaginator {
        $query = LaboratoryOrder::query()
            ->with([
                'patient',
                'items',
                'report',
            ])
            ->where(
                'referring_clinician_id',
                $account->referring_clinician_id
            );

        $this->applyOrderFilters(
            $query,
            $filters
        );

        return $query
            ->orderByDesc('ordered_at')
            ->orderByDesc('id')
            ->paginate(
                (int) ($filters['per_page'] ?? 25)
            );
    }

    public function findOrder(
        ClinicianPortalAccount $account,
        string $uuid
    ): LaboratoryOrder {
        return LaboratoryOrder::query()
            ->with([
                'patient',
                'items',
                'report',
            ])
            ->where(
                'referring_clinician_id',
                $account->referring_clinician_id
            )
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateReports(
        ClinicianPortalAccount $account,
        array $filters
    ): LengthAwarePaginator {
        $query = $this->ownedCurrentReports(
            $account
        );

        $search = $filters['search'] ?? null;

        if (
            is_string($search)
            && $search !== ''
        ) {
            $query->where(
                function ($query) use ($search): void {
                    $term = '%'.$search.'%';

                    $query
                        ->where(
                            'report_number',
                            'like',
                            $term
                        )
                        ->orWhere(
                            'order_snapshot->order_number',
                            'like',
                            $term
                        )
                        ->orWhere(
                            'patient_snapshot->medical_record_number',
                            'like',
                            $term
                        )
                        ->orWhere(
                            'patient_snapshot->full_name',
                            'like',
                            $term
                        );
                }
            );
        }

        return $query
            ->orderByDesc('generated_at')
            ->orderByDesc('id')
            ->paginate(
                (int) ($filters['per_page'] ?? 25)
            );
    }

    public function findCurrentReport(
        ClinicianPortalAccount $account,
        string $uuid
    ): LaboratoryReport {
        return $this->ownedCurrentReports(
            $account
        )
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function ownedCurrentReports(
        ClinicianPortalAccount $account
    ) {
        return LaboratoryReport::query()
            ->whereHas(
                'laboratoryOrder',
                fn ($query) => $query->where(
                    'referring_clinician_id',
                    $account->referring_clinician_id
                )
            )
            ->whereDoesntHave('supersededBy');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyOrderFilters(
        mixed $query,
        array $filters
    ): void {
        $search = $filters['search'] ?? null;

        if (
            is_string($search)
            && $search !== ''
        ) {
            $query->where(
                function ($query) use ($search): void {
                    $term = '%'.$search.'%';

                    $query
                        ->where(
                            'order_number',
                            'like',
                            $term
                        )
                        ->orWhereHas(
                            'patient',
                            function ($query) use ($term): void {
                                $query
                                    ->where(
                                        'medical_record_number',
                                        'like',
                                        $term
                                    )
                                    ->orWhere(
                                        'first_name',
                                        'like',
                                        $term
                                    )
                                    ->orWhere(
                                        'last_name',
                                        'like',
                                        $term
                                    )
                                    ->orWhereRaw(
                                        "CONCAT(first_name, ' ', last_name) LIKE ?",
                                        [$term]
                                    );
                            }
                        )
                        ->orWhereHas(
                            'items',
                            fn ($query) => $query->where(
                                'test_name_snapshot',
                                'like',
                                $term
                            )
                        );
                }
            );
        }

        $status = $filters['status'] ?? null;

        if (
            is_string($status)
            && $status !== ''
        ) {
            $query->where('status', $status);
        }
    }
}
