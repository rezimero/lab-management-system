<?php

namespace Tests\Support\Tenant;

use App\Models\LaboratoryOrder;
use App\Models\Specimen;
use App\Models\Tenant;
use Illuminate\Support\Str;
use RuntimeException;

abstract class SpecimenStatusTestCase extends SpecimenManagementTestCase
{
    /**
     * Create a collected specimen and attach order items.
     *
     * @param array<int, string>|null $orderItemUuids
     * @param array<string, mixed> $attributes
     */
    protected function createCollectedSpecimen(
        Tenant $tenant,
        LaboratoryOrder $order,
        ?array $orderItemUuids = null,
        array $attributes = []
    ): Specimen {
        return $this->runInTenant(
            $tenant,
            function () use (
                $order,
                $orderItemUuids,
                $attributes
            ): Specimen {
                $storedOrder = LaboratoryOrder::query()
                    ->where(
                        'uuid',
                        $order->uuid
                    )
                    ->with('items')
                    ->firstOrFail();

                $items = $storedOrder->items
                    ->where(
                        'status',
                        '!=',
                        'cancelled'
                    );

                if ($orderItemUuids !== null) {
                    $items = $items->whereIn(
                        'uuid',
                        $orderItemUuids
                    );
                }

                if ($items->isEmpty()) {
                    throw new RuntimeException(
                        'A specimen requires at least one active order item.'
                    );
                }

                $specimen = $storedOrder
                    ->specimens()
                    ->create(
                        array_merge(
                            [
                                'accession_number' => (
                                    'SP-TEST-'
                                    .strtoupper(
                                        Str::random(12)
                                    )
                                ),
                                'specimen_type' => 'Blood',
                                'container_type' => (
                                    'EDTA Tube'
                                ),
                                'collection_site' => (
                                    'Left arm'
                                ),
                                'volume' => '5.00',
                                'volume_unit' => 'mL',
                                'status' => 'collected',
                                'collected_by' => null,
                                'collected_at' => now(),
                                'received_at' => null,
                                'rejected_at' => null,
                                'rejection_reason' => null,
                                'notes' => null,
                            ],
                            $attributes
                        )
                    );

                $specimen
                    ->orderItems()
                    ->attach(
                        $items->modelKeys()
                    );

                if (
                    $storedOrder->status
                    === 'pending'
                ) {
                    $storedOrder->forceFill([
                        'status' => (
                            'sample_collected'
                        ),
                        'sample_collected_at' => now(),
                    ])->save();
                }

                return Specimen::query()
                    ->with([
                        'collectedBy',
                        'orderItems',
                    ])
                    ->whereKey(
                        $specimen->getKey()
                    )
                    ->firstOrFail();
            }
        );
    }

    /**
     * Retrieve a specimen from a tenant database.
     */
    protected function storedSpecimen(
        Tenant $tenant,
        string $uuid
    ): Specimen {
        return $this->runInTenant(
            $tenant,
            static fn (): Specimen => (
                Specimen::query()
                    ->where('uuid', $uuid)
                    ->firstOrFail()
            )
        );
    }
}