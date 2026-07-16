<?php

namespace Tests\Support\Tenant;

use App\Models\LaboratoryOrder;
use App\Models\Patient;
use App\Models\Specimen;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

abstract class SpecimenManagementTestCase extends LaboratoryOrderManagementTestCase
{
    /**
     * Register and authenticate a specimen-management tenant.
     *
     * @return array{
     *     registration: array<string, mixed>,
     *     tenant: Tenant,
     *     headers: array<string, string>
     * }
     */
    protected function authenticatedSpecimenContext(
        string $prefix = 'specimen'
    ): array {
        return $this->authenticatedLaboratoryOrderContext(
            $prefix
        );
    }

    /**
     * Create a pending order with catalogue-backed items.
     */
    protected function createPendingOrderWithItems(
        Tenant $tenant,
        Patient $patient,
        int $itemCount = 2
    ): LaboratoryOrder {
        $items = [];

        for (
            $index = 1;
            $index <= $itemCount;
            $index++
        ) {
            $labTest = $this->createLabTest(
                $tenant,
                [
                    'name' => (
                        'Specimen Test '.$index
                    ),
                    'category' => (
                        'Specimen Collection'
                    ),
                    'price' => (
                        1000 * $index
                    ),
                ]
            );

            $items[] = [
                'lab_test_id' => $labTest->id,
                'test_name_snapshot' => (
                    $labTest->name
                ),
                'test_category_snapshot' => (
                    $labTest->category
                ),
                'price_snapshot' => (
                    $labTest->price
                ),
                'turnaround_time_hours_snapshot' => (
                    $labTest->turnaround_time_hours
                ),
                'parameters_snapshot' => (
                    $labTest->parameters
                ),
                'status' => 'pending',
            ];
        }

        return $this->createLaboratoryOrder(
            $tenant,
            $patient,
            [
                'status' => 'pending',
                'sample_collected_at' => null,
            ],
            $items
        );
    }

    /**
     * Return specimens belonging to an order.
     *
     * @return Collection<int, Specimen>
     */
    protected function specimensForOrder(
        Tenant $tenant,
        string $orderUuid
    ): Collection {
        return $this->runInTenant(
            $tenant,
            static fn (): Collection => (
                Specimen::query()
                    ->with([
                        'collectedBy',
                        'orderItems',
                    ])
                    ->whereHas(
                        'order',
                        static fn ($query) => $query
                            ->where(
                                'uuid',
                                $orderUuid
                            )
                    )
                    ->orderBy('id')
                    ->get()
            )
        );
    }

    /**
     * Count specimens belonging to an order.
     */
    protected function specimenCount(
        Tenant $tenant,
        string $orderUuid
    ): int {
        return $this->specimensForOrder(
            $tenant,
            $orderUuid
        )->count();
    }

    /**
     * Count specimen-to-order-item links for an order.
     */
    protected function specimenItemLinkCount(
        Tenant $tenant,
        string $orderUuid
    ): int {
        return $this->runInTenant(
            $tenant,
            static fn (): int => (
                (int) DB::connection('tenant')
                    ->table(
                        'laboratory_order_item_specimen'
                    )
                    ->join(
                        'specimens',
                        'specimens.id',
                        '=',
                        'laboratory_order_item_specimen.specimen_id'
                    )
                    ->join(
                        'laboratory_orders',
                        'laboratory_orders.id',
                        '=',
                        'specimens.laboratory_order_id'
                    )
                    ->where(
                        'laboratory_orders.uuid',
                        $orderUuid
                    )
                    ->count()
            )
        );
    }
}
