<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Models\LaboratoryOrder;
use App\Models\LaboratoryReport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LaboratoryReportQueryService
{
    /**
     * Paginate tenant laboratory reports.
     *
     * @param array<string, mixed> $filters
     */
    public function paginate(
        array $filters
    ): LengthAwarePaginator {
        $query = LaboratoryReport::query()
            ->with('generatedBy');

        $search = $filters['search'] ?? null;

        if (is_string($search) && $search !== '') {
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
                            'patient_snapshot'
                            .'->medical_record_number',
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

        if (! empty($filters['order_uuid'])) {
            $query->where(
                'order_snapshot->uuid',
                $filters['order_uuid']
            );
        }

        if (! empty($filters['patient_id'])) {
            $query->where(
                'patient_snapshot->id',
                (int) $filters['patient_id']
            );
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate(
                'generated_at',
                '>=',
                $filters['from_date']
            );
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate(
                'generated_at',
                '<=',
                $filters['to_date']
            );
        }

        $sort = $filters['sort']
            ?? 'generated_at';

        $direction = $filters['direction']
            ?? 'desc';

        $query->orderBy(
            $sort,
            $direction
        );

        return $query->paginate(
            (int) ($filters['per_page'] ?? 25)
        );
    }

    /**
     * Retrieve the report issued for one order.
     */
    public function findForOrder(
        LaboratoryOrder $order
    ): LaboratoryReport {
        return LaboratoryReport::query()
            ->with('generatedBy')
            ->where(
                'laboratory_order_id',
                $order->id
            )
            ->firstOrFail();
    }
}
