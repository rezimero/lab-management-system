<?php

namespace App\Services\Tenant\Portal;

use App\Models\LaboratoryOrder;
use App\Models\LaboratoryReport;
use App\Models\PatientPortalAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PatientPortalReadService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateOrders(
        PatientPortalAccount $account,
        array $filters
    ): LengthAwarePaginator {
        $query = LaboratoryOrder::query()
            ->with([
                'items',
                'report',
            ])
            ->where(
                'patient_id',
                $account->patient_id
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
        PatientPortalAccount $account,
        string $uuid
    ): LaboratoryOrder {
        return LaboratoryOrder::query()
            ->with([
                'items',
                'report',
            ])
            ->where(
                'patient_id',
                $account->patient_id
            )
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateReports(
        PatientPortalAccount $account,
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
        PatientPortalAccount $account,
        string $uuid
    ): LaboratoryReport {
        return $this->ownedCurrentReports(
            $account
        )
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    private function ownedCurrentReports(
        PatientPortalAccount $account
    ) {
        return LaboratoryReport::query()
            ->whereHas(
                'laboratoryOrder',
                fn ($query) => $query->where(
                    'patient_id',
                    $account->patient_id
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
