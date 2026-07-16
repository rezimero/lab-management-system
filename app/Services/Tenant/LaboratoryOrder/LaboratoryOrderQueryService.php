<?php

namespace App\Services\Tenant\LaboratoryOrder;

use App\Models\LaboratoryOrder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LaboratoryOrderQueryService
{
    /**
     * Return a filtered and paginated tenant order listing.
     */
    public function paginate(
        ?string $search = null,
        ?string $status = null,
        ?string $priority = null,
        ?int $patientId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return LaboratoryOrder::query()
            ->with([
                'patient',
                'orderedBy',
                'items',
            ])
            ->when(
                $search,
                function ($query) use ($search): void {
                    $query->where(
                        function ($query) use ($search): void {
                            $query
                                ->where(
                                    'order_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhereHas(
                                    'patient',
                                    function ($query) use ($search): void {
                                        $query
                                            ->where(
                                                'first_name',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'last_name',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhere(
                                                'medical_record_number',
                                                'like',
                                                "%{$search}%"
                                            )
                                            ->orWhereRaw(
                                                "CONCAT(first_name, ' ', last_name) LIKE ?",
                                                ["%{$search}%"]
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->when(
                $status,
                fn ($query, string $value) => $query
                    ->where('status', $value)
            )
            ->when(
                $priority,
                fn ($query, string $value) => $query
                    ->where('priority', $value)
            )
            ->when(
                $patientId,
                fn ($query, int $value) => $query
                    ->where('patient_id', $value)
            )
            ->when(
                $dateFrom,
                fn ($query, string $value) => $query
                    ->whereDate(
                        'ordered_at',
                        '>=',
                        $value
                    )
            )
            ->when(
                $dateTo,
                fn ($query, string $value) => $query
                    ->whereDate(
                        'ordered_at',
                        '<=',
                        $value
                    )
            )
            ->orderByDesc('ordered_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Find an order using its public UUID.
     */
    public function find(string $uuid): LaboratoryOrder
    {
        return LaboratoryOrder::query()
            ->with([
                'patient',
                'orderedBy',
                'items',
                'specimens.collectedBy',
                'specimens.orderItems',
            ])
            ->where('uuid', $uuid)
            ->firstOrFail();
    }
}
