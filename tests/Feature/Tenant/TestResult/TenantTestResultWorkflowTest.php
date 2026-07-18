<?php

namespace Tests\Feature\Tenant\TestResult;

use Tests\Support\Tenant\TestResultManagementTestCase;

class TenantTestResultWorkflowTest extends TestResultManagementTestCase
{
    public function test_result_can_be_submitted_rejected_corrected_and_approved(): void
    {
        $context = $this
            ->authenticatedTestResultContext(
                'result-workflow'
            );

        $order = $this->createProcessingOrder(
            $context['tenant'],
            $context['headers']
        );

        $item = $order->items->firstOrFail();

        $this->startOrderItem(
            $context['headers'],
            $item
        )->assertOk();

        $createResponse = $this->createDraftResult(
            $context['headers'],
            $item
        )->assertCreated();

        $resultUuid = $createResponse->json(
            'data.uuid'
        );

        $this->assertIsString($resultUuid);

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/submit'
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Test result submitted for review successfully.'
            )
            ->assertJsonPath(
                'data.status',
                'submitted'
            )
            ->assertJsonPath(
                'data.rejection_reason',
                null
            );

        $this
            ->withHeaders($context['headers'])
            ->putJson(
                '/api/test-results/'
                .$resultUuid,
                [
                    'notes' => (
                        'Submitted results must be immutable.'
                    ),
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'test_result',
            ]);

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/reject',
                [
                    'rejection_reason' => (
                        '  Repeat haemoglobin reading  '
                    ),
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'rejected'
            )
            ->assertJsonPath(
                'data.rejection_reason',
                'Repeat haemoglobin reading'
            );

        $this
            ->withHeaders($context['headers'])
            ->putJson(
                '/api/test-results/'
                .$resultUuid,
                [
                    'results_data' => [
                        [
                            'name' => 'Haemoglobin',
                            'value' => '13.9',
                        ],
                        [
                            'name' => (
                                'White Blood Cell Count'
                            ),
                            'value' => '7.1',
                        ],
                    ],
                    'notes' => 'Corrected result.',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'draft'
            )
            ->assertJsonPath(
                'data.rejection_reason',
                null
            )
            ->assertJsonPath(
                'data.reviewed_by',
                null
            )
            ->assertJsonPath(
                'data.reviewed_at',
                null
            );

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/submit'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'submitted'
            );

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/approve'
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Test result approved successfully.'
            )
            ->assertJsonPath(
                'data.status',
                'approved'
            )
            ->assertJsonPath(
                'data.order_item.status',
                'completed'
            );

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/approve'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'test_result',
            ]);

        $storedResult = $this->storedTestResult(
            $context['tenant'],
            $resultUuid
        );

        $storedItem = $this->storedOrderItem(
            $context['tenant'],
            $item->uuid
        );

        $this->assertSame(
            'approved',
            $storedResult->status
        );

        $this->assertNotNull(
            $storedResult->submitted_by
        );

        $this->assertNotNull(
            $storedResult->submitted_at
        );

        $this->assertNotNull(
            $storedResult->reviewed_by
        );

        $this->assertNotNull(
            $storedResult->reviewed_at
        );

        $this->assertSame(
            'completed',
            $storedItem->status
        );

        $this->assertNotNull(
            $storedItem->completed_at
        );
    }
}
