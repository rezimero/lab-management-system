<?php

namespace Tests\Feature\Tenant\TestResult;

use Tests\Support\Tenant\TestResultManagementTestCase;

class TenantTestResultCompletionReadinessTest extends TestResultManagementTestCase
{
    public function test_order_requires_approved_results_for_every_active_item(): void
    {
        $context = $this
            ->authenticatedTestResultContext(
                'result-completion'
            );

        $order = $this->createProcessingOrder(
            $context['tenant'],
            $context['headers'],
            2
        );

        $items = $order->items->values();

        $firstItem = $items->get(0);
        $secondItem = $items->get(1);

        $this->assertNotNull($firstItem);
        $this->assertNotNull($secondItem);

        $this->startOrderItem(
            $context['headers'],
            $firstItem
        )->assertOk();

        $this->startOrderItem(
            $context['headers'],
            $secondItem
        )->assertOk();

        $firstResponse = $this
            ->createDraftResult(
                $context['headers'],
                $firstItem
            )
            ->assertCreated();

        $firstResultUuid = $firstResponse->json(
            'data.uuid'
        );

        $this->assertIsString(
            $firstResultUuid
        );

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$firstResultUuid
                .'/submit'
            )
            ->assertOk();

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$firstResultUuid
                .'/approve'
            )
            ->assertOk();

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

        $storedOrder = $this
            ->storedProcessingOrder(
                $context['tenant'],
                $order->uuid
            );

        $this->assertSame(
            'in_progress',
            $storedOrder->status
        );

        $this->assertNull(
            $storedOrder->completed_at
        );

        $secondResponse = $this
            ->createDraftResult(
                $context['headers'],
                $secondItem
            )
            ->assertCreated();

        $secondResultUuid = $secondResponse->json(
            'data.uuid'
        );

        $this->assertIsString(
            $secondResultUuid
        );

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$secondResultUuid
                .'/submit'
            )
            ->assertOk();

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$secondResultUuid
                .'/approve'
            )
            ->assertOk();

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
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'completed'
            );

        $completedOrder = $this
            ->storedProcessingOrder(
                $context['tenant'],
                $order->uuid
            );

        $this->assertSame(
            'completed',
            $completedOrder->status
        );

        $this->assertNotNull(
            $completedOrder->completed_at
        );

        foreach (
            $completedOrder->items as $item
        ) {
            $this->assertSame(
                'completed',
                $item->status
            );

            $this->assertNotNull(
                $item->testResult
            );

            $this->assertSame(
                'approved',
                $item->testResult->status
            );
        }
    }
}
