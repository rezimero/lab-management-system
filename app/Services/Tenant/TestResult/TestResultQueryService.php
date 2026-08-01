<?php

namespace App\Services\Tenant\TestResult;

use App\Models\TestResult;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TestResultQueryService
{
    /**
     * Relations required by result responses.
     *
     * @var array<int, string>
     */
    private const RELATIONS = [
        'orderItem.order.patient',
        'enteredBy',
        'submittedBy',
        'reviewedBy',
        'latestAmendment',
    ];

    /**
     * Return paginated order-driven results.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginate(
        array $filters
    ): LengthAwarePaginator {
        $query = TestResult::query()
            ->with(self::RELATIONS)
            ->whereNotNull(
                'laboratory_order_item_id'
            );

        $this->applyFilters(
            $query,
            $filters
        );

        $sort = $filters['sort']
            ?? 'created_at';

        $direction = $filters['direction']
            ?? 'desc';

        return $query
            ->orderBy($sort, $direction)
            ->paginate(
                (int) (
                    $filters['per_page']
                    ?? 15
                )
            )
            ->withQueryString();
    }

    /**
     * Find an order-driven result by UUID.
     */
    public function find(
        string $uuid
    ): TestResult {
        return TestResult::query()
            ->with(self::RELATIONS)
            ->whereNotNull(
                'laboratory_order_item_id'
            )
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * Return workflow statistics.
     *
     * @return array<string, int>
     */
    public function statistics(): array
    {
        $counts = TestResult::query()
            ->whereNotNull(
                'laboratory_order_item_id'
            )
            ->selectRaw(
                'status, COUNT(*) AS aggregate'
            )
            ->groupBy('status')
            ->pluck(
                'aggregate',
                'status'
            );

        return [
            'total_results' => (
                (int) $counts->sum()
            ),
            'draft' => (
                (int) $counts->get(
                    TestResult::STATUS_DRAFT,
                    0
                )
            ),
            'submitted' => (
                (int) $counts->get(
                    TestResult::STATUS_SUBMITTED,
                    0
                )
            ),
            'approved' => (
                (int) $counts->get(
                    TestResult::STATUS_APPROVED,
                    0
                )
            ),
            'rejected' => (
                (int) $counts->get(
                    TestResult::STATUS_REJECTED,
                    0
                )
            ),
        ];
    }

    /**
     * Apply supported result filters.
     *
     * @param  Builder<TestResult>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(
        Builder $query,
        array $filters
    ): void {
        if (! empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        if (! empty($filters['order_item_uuid'])) {
            $query->whereHas(
                'orderItem',
                fn (Builder $item): Builder => (
                    $item->where(
                        'uuid',
                        $filters['order_item_uuid']
                    )
                )
            );
        }

        if (! empty($filters['order_uuid'])) {
            $query->whereHas(
                'orderItem.order',
                fn (Builder $order): Builder => (
                    $order->where(
                        'uuid',
                        $filters['order_uuid']
                    )
                )
            );
        }

        if (! empty($filters['patient_id'])) {
            $query->whereHas(
                'orderItem.order',
                fn (Builder $order): Builder => (
                    $order->where(
                        'patient_id',
                        $filters['patient_id']
                    )
                )
            );
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate(
                'created_at',
                '>=',
                $filters['from_date']
            );
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate(
                'created_at',
                '<=',
                $filters['to_date']
            );
        }

        if (! empty($filters['search'])) {
            $this->applySearch(
                $query,
                $filters['search']
            );
        }
    }

    /**
     * Search result, order, test and patient fields.
     *
     * @param  Builder<TestResult>  $query
     */
    private function applySearch(
        Builder $query,
        string $search
    ): void {
        $term = '%'.$search.'%';

        $query->where(
            function (Builder $result) use (
                $term
            ): void {
                $result
                    ->whereHas(
                        'orderItem',
                        fn (Builder $item): Builder => (
                            $item->where(
                                'test_name_snapshot',
                                'like',
                                $term
                            )
                        )
                    )
                    ->orWhereHas(
                        'orderItem.order',
                        fn (Builder $order): Builder => (
                            $order->where(
                                'order_number',
                                'like',
                                $term
                            )
                        )
                    )
                    ->orWhereHas(
                        'orderItem.order.patient',
                        function (
                            Builder $patient
                        ) use ($term): void {
                            $patient
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
                                );
                        }
                    );
            }
        );
    }
}
