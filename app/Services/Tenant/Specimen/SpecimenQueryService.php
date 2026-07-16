<?php

namespace App\Services\Tenant\Specimen;

use App\Models\Specimen;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SpecimenQueryService
{
    /**
     * Return a filtered specimen listing.
     */
    public function paginate(
        ?string $search = null,
        ?string $status = null,
        ?string $orderUuid = null,
        ?string $specimenType = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return $this->query()
            ->when(
                $search,
                function (
                    Builder $query,
                    string $value
                ): void {
                    $query->where(
                        function (
                            Builder $query
                        ) use ($value): void {
                            $query
                                ->where(
                                    'accession_number',
                                    'like',
                                    "%{$value}%"
                                )
                                ->orWhere(
                                    'specimen_type',
                                    'like',
                                    "%{$value}%"
                                )
                                ->orWhere(
                                    'container_type',
                                    'like',
                                    "%{$value}%"
                                )
                                ->orWhere(
                                    'collection_site',
                                    'like',
                                    "%{$value}%"
                                )
                                ->orWhereHas(
                                    'order',
                                    function (
                                        Builder $query
                                    ) use ($value): void {
                                        $query
                                            ->where(
                                                'order_number',
                                                'like',
                                                "%{$value}%"
                                            )
                                            ->orWhereHas(
                                                'patient',
                                                function (
                                                    Builder $query
                                                ) use ($value): void {
                                                    $query
                                                        ->where(
                                                            'first_name',
                                                            'like',
                                                            "%{$value}%"
                                                        )
                                                        ->orWhere(
                                                            'last_name',
                                                            'like',
                                                            "%{$value}%"
                                                        )
                                                        ->orWhere(
                                                            'medical_record_number',
                                                            'like',
                                                            "%{$value}%"
                                                        )
                                                        ->orWhereRaw(
                                                            "CONCAT(first_name, ' ', last_name) LIKE ?",
                                                            ["%{$value}%"]
                                                        );
                                                }
                                            );
                                    }
                                );
                        }
                    );
                }
            )
            ->when(
                $status,
                fn (
                    Builder $query,
                    string $value
                ): Builder => $query->where(
                    'status',
                    $value
                )
            )
            ->when(
                $orderUuid,
                fn (
                    Builder $query,
                    string $value
                ): Builder => $query->whereHas(
                    'order',
                    fn (Builder $query): Builder => (
                        $query->where(
                            'uuid',
                            $value
                        )
                    )
                )
            )
            ->when(
                $specimenType,
                fn (
                    Builder $query,
                    string $value
                ): Builder => $query->where(
                    'specimen_type',
                    $value
                )
            )
            ->when(
                $dateFrom,
                fn (
                    Builder $query,
                    string $value
                ): Builder => $query->whereDate(
                    'collected_at',
                    '>=',
                    $value
                )
            )
            ->when(
                $dateTo,
                fn (
                    Builder $query,
                    string $value
                ): Builder => $query->whereDate(
                    'collected_at',
                    '<=',
                    $value
                )
            )
            ->orderByDesc('collected_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Find a specimen using its public UUID.
     */
    public function find(string $uuid): Specimen
    {
        return $this->query()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    /**
     * Return tenant specimen status totals.
     *
     * @return array<string, int>
     */
    public function stats(): array
    {
        $counts = Specimen::query()
            ->selectRaw(
                'status, COUNT(*) as aggregate'
            )
            ->groupBy('status')
            ->pluck(
                'aggregate',
                'status'
            );

        return [
            'total' => (int) $counts->sum(),
            'collected' => (int) (
                $counts->get('collected', 0)
            ),
            'received' => (int) (
                $counts->get('received', 0)
            ),
            'rejected' => (int) (
                $counts->get('rejected', 0)
            ),
        ];
    }

    /**
     * Build the specimen relationship query.
     */
    private function query(): Builder
    {
        return Specimen::query()
            ->with([
                'collectedBy',
                'order.patient',
                'orderItems',
            ]);
    }
}