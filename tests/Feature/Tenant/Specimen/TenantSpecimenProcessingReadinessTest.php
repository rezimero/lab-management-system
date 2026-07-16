<?php

namespace Tests\Feature\Tenant\Specimen;

use App\Models\LaboratoryOrder;
use App\Models\Tenant;
use Tests\Support\Tenant\SpecimenStatusTestCase;

class TenantSpecimenProcessingReadinessTest extends SpecimenStatusTestCase
{
    public function test_every_active_item_requires_a_received_specimen(): void
    {
        $context = $this->authenticatedSpecimenContext(
            'processing-readiness'
        );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $order = $this->createPendingOrderWithItems(
            $context['tenant'],
            $patient
        );

        $itemUuids = $this->orderItemUuids(
            $context['tenant'],
            $order->uuid
        );

        $first = $this->createCollectedSpecimen(
            $context['tenant'],
            $order,
            [
                $itemUuids[0],
            ]
        );

        $second = $this->createCollectedSpecimen(
            $context['tenant'],
            $order,
            [
                $itemUuids[1],
            ]
        );

        $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/specimens/'
                .$first->uuid
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
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
            ]);

        $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/specimens/'
                .$second->uuid
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
    }

    public function test_received_replacement_restores_processing_readiness(): void
    {
        $context = $this->authenticatedSpecimenContext(
            'replacement-readiness'
        );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $order = $this->createPendingOrderWithItems(
            $context['tenant'],
            $patient
        );

        $source = $this->createCollectedSpecimen(
            $context['tenant'],
            $order,
            null,
            [
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => (
                    'Haemolysed specimen'
                ),
            ]
        );

        $replacementResponse = $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/specimens/'
                .$source->uuid
                .'/replacement',
                []
            )
            ->assertCreated();

        $replacementUuid = (
            $replacementResponse->json(
                'data.uuid'
            )
        );

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
            ->assertUnprocessable();

        $this
            ->withHeaders($context['headers'])
            ->patchJson(
                '/api/specimens/'
                .$replacementUuid
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
}
