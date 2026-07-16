<?php

namespace App\Services\Tenant\Specimen;

use App\Models\LaboratoryOrder;
use App\Models\Specimen;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SpecimenCollectionService
{
    /**
     * Collect specimens for every active order item.
     *
     * @param array<string, mixed> $payload
     */
    public function collect(
        string $orderUuid,
        array $payload,
        ?int $collectorId
    ): LaboratoryOrder {
        return DB::connection('tenant')
            ->transaction(
                function () use (
                    $orderUuid,
                    $payload,
                    $collectorId
                ): LaboratoryOrder {
                    $order = LaboratoryOrder::query()
                        ->where('uuid', $orderUuid)
                        ->with('items')
                        ->lockForUpdate()
                        ->firstOrFail();

                    $this->assertOrderIsPending(
                        $order
                    );

                    $activeItems = $order->items
                        ->where(
                            'status',
                            '!=',
                            'cancelled'
                        )
                        ->keyBy('uuid');

                    $this->assertItemCoverage(
                        $activeItems,
                        $payload['specimens']
                    );

                    foreach (
                        $payload['specimens']
                        as $specimenData
                    ) {
                        $this->createSpecimen(
                            $order,
                            $specimenData,
                            $activeItems,
                            $collectorId
                        );
                    }

                    $order->forceFill([
                        'status' => 'sample_collected',
                        'sample_collected_at' => now(),
                    ])->save();

                    return $order
                        ->fresh()
                        ->load([
                            'patient',
                            'orderedBy',
                            'items',
                            'specimens.collectedBy',
                            'specimens.orderItems',
                        ]);
                },
                3
            );
    }

    /**
     * Ensure collection only occurs once.
     */
    private function assertOrderIsPending(
        LaboratoryOrder $order
    ): void {
        if ($order->status === 'pending') {
            return;
        }

        throw ValidationException::withMessages([
            'status' => [
                'Specimens can only be collected for a pending order.',
            ],
        ]);
    }

    /**
     * Ensure selected items belong to the order and all are covered.
     *
     * @param Collection<string, mixed> $activeItems
     * @param array<int, array<string, mixed>> $specimens
     */
    private function assertItemCoverage(
        Collection $activeItems,
        array $specimens
    ): void {
        if ($activeItems->isEmpty()) {
            throw ValidationException::withMessages([
                'specimens' => [
                    'The order has no active items requiring specimens.',
                ],
            ]);
        }

        $submitted = collect($specimens)
            ->flatMap(
                fn (array $specimen): array => (
                    $specimen['order_item_uuids']
                )
            )
            ->unique()
            ->values();

        if (
            $submitted
                ->diff($activeItems->keys())
                ->isNotEmpty()
        ) {
            throw ValidationException::withMessages([
                'specimens' => [
                    'One or more selected order items do not belong to this order.',
                ],
            ]);
        }

        if (
            $activeItems
                ->keys()
                ->diff($submitted)
                ->isNotEmpty()
        ) {
            throw ValidationException::withMessages([
                'specimens' => [
                    'Every active order item must be assigned to a specimen.',
                ],
            ]);
        }
    }

    /**
     * Create one specimen and attach its order items.
     *
     * @param array<string, mixed> $data
     * @param Collection<string, mixed> $activeItems
     */
    private function createSpecimen(
        LaboratoryOrder $order,
        array $data,
        Collection $activeItems,
        ?int $collectorId
    ): void {
        $specimen = $order->specimens()->create([
            'accession_number' => (
                $this->generateAccessionNumber()
            ),
            'specimen_type' => (
                trim($data['specimen_type'])
            ),
            'container_type' => (
                $data['container_type'] ?? null
            ),
            'collection_site' => (
                $data['collection_site'] ?? null
            ),
            'volume' => $data['volume'] ?? null,
            'volume_unit' => (
                $data['volume_unit'] ?? null
            ),
            'status' => 'collected',
            'collected_by' => $collectorId,
            'collected_at' => isset(
                $data['collected_at']
            )
                ? Carbon::parse(
                    $data['collected_at']
                )
                : now(),
            'notes' => $data['notes'] ?? null,
        ]);

        $itemIds = collect(
            $data['order_item_uuids']
        )
            ->map(
                function (
                    string $itemUuid
                ) use ($activeItems): int {
                    $item = $activeItems->get(
                        $itemUuid
                    );

                    if ($item === null) {
                        throw ValidationException::withMessages([
                            'specimens' => [
                                'One or more selected order items do not belong to this order.',
                            ],
                        ]);
                    }

                    return (int) $item->getKey();
                }
            )
            ->values()
            ->all();

        $specimen->orderItems()->attach(
            $itemIds
        );
    }

    /**
     * Generate a tenant-unique accession number.
     */
    private function generateAccessionNumber(): string
    {
        do {
            $accessionNumber = sprintf(
                'SP-%s-%s',
                now()->format('Ymd'),
                strtoupper(
                    Str::random(8)
                )
            );
        } while (
            Specimen::query()
                ->where(
                    'accession_number',
                    $accessionNumber
                )
                ->exists()
        );

        return $accessionNumber;
    }
}
