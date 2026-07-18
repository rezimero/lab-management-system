<?php

namespace Tests\Feature\Tenant\LaboratoryOrder;

use App\Models\LaboratoryOrder;
use App\Models\Tenant;
use Tests\Support\Tenant\SpecimenManagementTestCase;

class TenantLaboratoryOrderStatusTransitionTest extends SpecimenManagementTestCase
{
    public function test_order_reaches_processing_after_specimen_receiving(): void
    {
        $context = $this
            ->authenticatedSpecimenContext(
                'order-processing-workflow'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $order = $this
            ->createPendingOrderWithItems(
                $context['tenant'],
                $patient
            );

        $itemUuids = $this->orderItemUuids(
            $context['tenant'],
            $order->uuid
        );

        $collectionResponse = $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/laboratory-orders/'
                .$order->uuid
                .'/specimens',
                [
                    'specimens' => [
                        [
                            'specimen_type' => 'Blood',
                            'container_type' => (
                                'EDTA Tube'
                            ),
                            'order_item_uuids' => (
                                $itemUuids
                            ),
                        ],
                    ],
                ]
            );

        $collectionResponse
            ->assertCreated()
            ->assertJsonPath(
                'data.status',
                'sample_collected'
            );

        $specimenUuid = $collectionResponse
            ->json(
                'data.specimens.0.uuid'
            );

        $this->assertIsString(
            $specimenUuid
        );

        $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/specimens/'
                .$specimenUuid
                .'/status',
                [
                    'status' => 'received',
                ]
            )
            ->assertOk();

        $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/laboratory-orders/'
                .$order->uuid
                .'/status',
                [
                    'status' => 'in_progress',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'in_progress'
            );

        $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/laboratory-orders/'
                .$order->uuid
                .'/status',
                [
                    'status' => 'completed',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ]);

        $stored = $this->storedOrder(
            $context['tenant'],
            $order->uuid
        );

        $this->assertSame(
            'in_progress',
            $stored->status
        );

        $this->assertNotNull(
            $stored->sample_collected_at
        );

        $this->assertNull(
            $stored->completed_at
        );
    }

    public function test_order_can_be_cancelled_from_permitted_stages(): void
    {
        $context = $this
            ->authenticatedSpecimenContext(
                'order-cancellation'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $pendingOrder = $this
            ->createPendingOrderWithItems(
                $context['tenant'],
                $patient
            );

        $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/laboratory-orders/'
                .$pendingOrder->uuid
                .'/status',
                [
                    'status' => 'cancelled',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'cancelled'
            );

        $collectedOrder = $this
            ->createPendingOrderWithItems(
                $context['tenant'],
                $patient
            );

        $itemUuids = $this->orderItemUuids(
            $context['tenant'],
            $collectedOrder->uuid
        );

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/laboratory-orders/'
                .$collectedOrder->uuid
                .'/specimens',
                [
                    'specimens' => [
                        [
                            'specimen_type' => 'Blood',
                            'order_item_uuids' => (
                                $itemUuids
                            ),
                        ],
                    ],
                ]
            )
            ->assertCreated();

        $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/laboratory-orders/'
                .$collectedOrder->uuid
                .'/status',
                [
                    'status' => 'cancelled',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'cancelled'
            );

        $stored = $this->storedOrder(
            $context['tenant'],
            $collectedOrder->uuid
        );

        $this->assertNotNull(
            $stored->cancelled_at
        );
    }

    /**
     * Return active order-item UUIDs.
     *
     * @return array<int, string>
     */
    private function orderItemUuids(
        Tenant $tenant,
        string $orderUuid
    ): array {
        return $this->runInTenant(
            $tenant,
            static fn (): array => (
                LaboratoryOrder::query()
                    ->with('items')
                    ->where('uuid', $orderUuid)
                    ->firstOrFail()
                    ->items
                    ->pluck('uuid')
                    ->values()
                    ->all()
            )
        );
    }

    /**
     * Retrieve an order from its tenant database.
     */
    private function storedOrder(
        Tenant $tenant,
        string $orderUuid
    ): LaboratoryOrder {
        return $this->runInTenant(
            $tenant,
            static fn (): LaboratoryOrder => (
                LaboratoryOrder::query()
                    ->where(
                        'uuid',
                        $orderUuid
                    )
                    ->firstOrFail()
            )
        );
    }
}
