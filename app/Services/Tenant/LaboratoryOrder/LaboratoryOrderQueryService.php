<?php

namespace App\Services\Tenant\LaboratoryOrder;

use App\Models\LaboratoryOrder;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LaboratoryOrderQueryService
{
    public function paginate(
        ?string $search = null,
        ?string $status = null,
        ?string $priority = null,
        ?int $patientId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?string $branchUuid = null,
        ?TenantUser $user = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $assignedBranchIds = $this->assignedBranchIds($user);

        return LaboratoryOrder::query()
            ->with([
                'patient',
                'orderedBy',
                'collectionBranch',
                'processingBranch',
                'priceList',
                'referringClinician',
                'items.testResult.enteredBy',
                'items.testResult.submittedBy',
                'items.testResult.reviewedBy',
                'items.testResult.latestAmendment',
            ])
            ->when(
                $user !== null && ! $user->isAdmin(),
                fn ($query) => $query->where(function ($query) use ($assignedBranchIds): void {
                    $query->whereIn('collection_branch_id', $assignedBranchIds)
                        ->orWhereIn('processing_branch_id', $assignedBranchIds);
                })
            )
            ->when(
                $search,
                function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('order_number', 'like', "%{$search}%")
                            ->orWhere('referring_clinician', 'like', "%{$search}%")
                            ->orWhereHas('referringClinician', function ($query) use ($search): void {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('organisation', 'like', "%{$search}%")
                                    ->orWhere('registration_number', 'like', "%{$search}%");
                            })
                            ->orWhereHas('patient', function ($query) use ($search): void {
                                $query->where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('medical_record_number', 'like', "%{$search}%")
                                    ->orWhereRaw(
                                        "CONCAT(first_name, ' ', last_name) LIKE ?",
                                        ["%{$search}%"]
                                    );
                            });
                    });
                }
            )
            ->when($status, fn ($query, string $value) => $query->where('status', $value))
            ->when($priority, fn ($query, string $value) => $query->where('priority', $value))
            ->when($patientId, fn ($query, int $value) => $query->where('patient_id', $value))
            ->when($dateFrom, fn ($query, string $value) => $query->whereDate('ordered_at', '>=', $value))
            ->when($dateTo, fn ($query, string $value) => $query->whereDate('ordered_at', '<=', $value))
            ->when(
                $branchUuid,
                fn ($query, string $value) => $query->where(function ($query) use ($value): void {
                    $query->whereHas(
                        'collectionBranch',
                        fn ($query) => $query->where('uuid', $value)
                    )->orWhereHas(
                        'processingBranch',
                        fn ($query) => $query->where('uuid', $value)
                    );
                })
            )
            ->orderByDesc('ordered_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function find(
        string $uuid,
        ?TenantUser $user = null
    ): LaboratoryOrder {
        $assignedBranchIds = $this->assignedBranchIds($user);

        return LaboratoryOrder::query()
            ->with([
                'patient',
                'orderedBy',
                'collectionBranch',
                'processingBranch',
                'priceList',
                'referringClinician',
                'invoice',
                'items.testResult.enteredBy',
                'items.testResult.submittedBy',
                'items.testResult.reviewedBy',
                'items.testResult.latestAmendment',
                'specimens.collectedBy',
                'specimens.orderItems',
            ])
            ->when(
                $user !== null && ! $user->isAdmin(),
                fn ($query) => $query->where(function ($query) use ($assignedBranchIds): void {
                    $query->whereIn('collection_branch_id', $assignedBranchIds)
                        ->orWhereIn('processing_branch_id', $assignedBranchIds);
                })
            )
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * @return array<int, int>
     */
    private function assignedBranchIds(?TenantUser $user): array
    {
        if ($user === null || $user->isAdmin()) {
            return [];
        }

        return $user->branches()
            ->pluck('branches.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }
}
