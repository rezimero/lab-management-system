<?php

namespace Tests\Feature\Tenant\Specimen;

use Tests\Support\Tenant\SpecimenManagementTestCase;

class TenantSpecimenDuplicateItemValidationTest extends SpecimenManagementTestCase
{
    public function test_item_cannot_be_repeated_within_one_specimen(): void
    {
        $context = $this
            ->authenticatedSpecimenContext(
                'specimen-duplicate-item'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $order = $this
            ->createPendingOrderWithItems(
                $context['tenant'],
                $patient,
                1
            );

        $itemUuid = $order
            ->items[0]
            ->uuid;

        $response = $this
            ->withHeaders(
                $context['headers']
            )
            ->postJson(
                '/api/laboratory-orders/'
                .$order->uuid
                .'/specimens',
                [
                    'specimens' => [
                        [
                            'specimen_type' => (
                                'Whole Blood'
                            ),
                            'order_item_uuids' => [
                                $itemUuid,
                                $itemUuid,
                            ],
                        ],
                    ],
                ]
            );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'specimens.0.order_item_uuids',
            ]);

        $errors = $response->json(
            'errors'
        );

        self::assertIsArray($errors);

        self::assertSame(
            'An order item cannot be repeated within one specimen.',
            $errors[
                'specimens.0.order_item_uuids'
            ][0] ?? null
        );

        self::assertSame(
            0,
            $this->specimenCount(
                $context['tenant'],
                $order->uuid
            )
        );

        $storedOrder = $this
            ->findLaboratoryOrder(
                $context['tenant'],
                $order->uuid
            );

        self::assertNotNull($storedOrder);

        self::assertSame(
            'pending',
            $storedOrder->status
        );

        self::assertNull(
            $storedOrder->sample_collected_at
        );
    }
}
