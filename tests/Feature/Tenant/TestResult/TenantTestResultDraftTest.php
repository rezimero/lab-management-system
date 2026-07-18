<?php

namespace Tests\Feature\Tenant\TestResult;

use Tests\Support\Tenant\TestResultManagementTestCase;

class TenantTestResultDraftTest extends TestResultManagementTestCase
{
    public function test_order_item_can_start_and_receive_one_editable_draft(): void
    {
        $context = $this
            ->authenticatedTestResultContext(
                'result-draft'
            );

        $order = $this->createProcessingOrder(
            $context['tenant'],
            $context['headers']
        );

        $item = $order->items->firstOrFail();

        $this->startOrderItem(
            $context['headers'],
            $item
        )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Laboratory order item processing started successfully.'
            )
            ->assertJsonPath(
                'data.uuid',
                $item->uuid
            )
            ->assertJsonPath(
                'data.status',
                'in_progress'
            );

        $createResponse = $this
            ->createDraftResult(
                $context['headers'],
                $item,
                [
                    'results_data' => [
                        [
                            'name' => (
                                '  Haemoglobin  '
                            ),
                            'value' => '  13.4  ',
                            'unit' => ' g/dL ',
                            'reference_range' => (
                                ' 12.0 - 16.0 '
                            ),
                            'flag' => 'normal',
                        ],
                        [
                            'name' => (
                                ' White Blood Cell Count '
                            ),
                            'value' => ' 7.0 ',
                            'unit' => ' 10^9/L ',
                            'reference_range' => (
                                ' 4.0 - 11.0 '
                            ),
                            'flag' => 'normal',
                        ],
                    ],
                    'notes' => (
                        '  Initial draft  '
                    ),
                ]
            );

        $createResponse
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Draft test result created successfully.'
            )
            ->assertJsonPath(
                'data.status',
                'draft'
            )
            ->assertJsonPath(
                'data.order_item.uuid',
                $item->uuid
            )
            ->assertJsonPath(
                'data.results_data.0.name',
                'Haemoglobin'
            )
            ->assertJsonPath(
                'data.results_data.0.value',
                '13.4'
            )
            ->assertJsonPath(
                'data.notes',
                'Initial draft'
            );

        $resultUuid = $createResponse->json(
            'data.uuid'
        );

        $this->assertIsString($resultUuid);

        $this
            ->withHeaders($context['headers'])
            ->putJson(
                '/api/test-results/'
                .$resultUuid,
                [
                    'results_data' => [
                        [
                            'name' => 'Haemoglobin',
                            'value' => '13.8',
                        ],
                        [
                            'name' => (
                                'White Blood Cell Count'
                            ),
                            'value' => '7.4',
                        ],
                    ],
                    'notes' => '   ',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Draft test result updated successfully.'
            )
            ->assertJsonPath(
                'data.status',
                'draft'
            )
            ->assertJsonPath(
                'data.results_data.0.value',
                '13.8'
            )
            ->assertJsonPath(
                'data.notes',
                null
            );

        $this->createDraftResult(
            $context['headers'],
            $item
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'order_item',
            ]);

        $stored = $this->storedTestResult(
            $context['tenant'],
            $resultUuid
        );

        $this->assertSame(
            'draft',
            $stored->status
        );

        $this->assertSame(
            $item->id,
            $stored->laboratory_order_item_id
        );

        $this->assertNotNull(
            $stored->entered_by
        );

        $this->assertNull(
            $stored->submitted_at
        );

        $this->assertNull(
            $stored->reviewed_at
        );
    }
}
