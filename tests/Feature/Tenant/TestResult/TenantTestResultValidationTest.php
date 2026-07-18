<?php

namespace Tests\Feature\Tenant\TestResult;

use App\Models\LaboratoryOrderItem;
use Tests\Support\Tenant\TestResultManagementTestCase;

class TenantTestResultValidationTest extends TestResultManagementTestCase
{
    public function test_processing_and_result_workflow_validation_is_enforced(): void
    {
        $context = $this
            ->authenticatedTestResultContext(
                'result-validation'
            );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $uncoveredOrder = $this
            ->createLaboratoryOrder(
                $context['tenant'],
                $patient,
                [
                    'status' => 'in_progress',
                ],
                [
                    [
                        'test_name_snapshot' => (
                            'Uncovered Processing Test'
                        ),
                        'test_category_snapshot' => (
                            'Haematology'
                        ),
                        'parameters_snapshot' => (
                            $this->defaultResultParameters()
                        ),
                        'status' => 'pending',
                    ],
                ]
            );

        $uncoveredItem = $uncoveredOrder
            ->items
            ->firstOrFail();

        $this->startOrderItem(
            $context['headers'],
            $uncoveredItem
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'order_item',
            ]);

        $terminalOrder = $this
            ->createLaboratoryOrder(
                $context['tenant'],
                $patient,
                [
                    'status' => 'in_progress',
                ],
                [
                    [
                        'test_name_snapshot' => (
                            'Completed Processing Test'
                        ),
                        'test_category_snapshot' => (
                            'Haematology'
                        ),
                        'parameters_snapshot' => (
                            $this->defaultResultParameters()
                        ),
                        'status' => 'completed',
                        'completed_at' => now(),
                    ],
                    [
                        'test_name_snapshot' => (
                            'Cancelled Processing Test'
                        ),
                        'test_category_snapshot' => (
                            'Haematology'
                        ),
                        'parameters_snapshot' => (
                            $this->defaultResultParameters()
                        ),
                        'status' => 'cancelled',
                    ],
                ]
            );

        $terminalItems = $terminalOrder
            ->items
            ->values();

        $completedItem = $terminalItems->get(0);
        $cancelledItem = $terminalItems->get(1);

        $this->assertInstanceOf(
            LaboratoryOrderItem::class,
            $completedItem
        );

        $this->assertInstanceOf(
            LaboratoryOrderItem::class,
            $cancelledItem
        );

        $this->startOrderItem(
            $context['headers'],
            $completedItem
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'order_item',
            ]);

        $this->startOrderItem(
            $context['headers'],
            $cancelledItem
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'order_item',
            ]);

        $notStartedOrder = $this
            ->createProcessingOrder(
                $context['tenant'],
                $context['headers']
            );

        $notStartedItem = $notStartedOrder
            ->items
            ->firstOrFail();

        $this->createDraftResult(
            $context['headers'],
            $notStartedItem
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'order_item',
            ]);

        $emptyOrder = $this
            ->createProcessingOrder(
                $context['tenant'],
                $context['headers']
            );

        $emptyItem = $emptyOrder
            ->items
            ->firstOrFail();

        $this->startOrderItem(
            $context['headers'],
            $emptyItem
        )->assertOk();

        $emptyResponse = $this
            ->createDraftResult(
                $context['headers'],
                $emptyItem,
                [
                    'results_data' => [],
                ]
            )
            ->assertCreated();

        $emptyResultUuid = $emptyResponse->json(
            'data.uuid'
        );

        $this->assertIsString(
            $emptyResultUuid
        );

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$emptyResultUuid
                .'/submit'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'results_data',
            ]);

        $missingOrder = $this
            ->createProcessingOrder(
                $context['tenant'],
                $context['headers']
            );

        $missingItem = $missingOrder
            ->items
            ->firstOrFail();

        $this->startOrderItem(
            $context['headers'],
            $missingItem
        )->assertOk();

        $missingResponse = $this
            ->createDraftResult(
                $context['headers'],
                $missingItem,
                [
                    'results_data' => [
                        [
                            'name' => 'Haemoglobin',
                            'value' => '13.1',
                        ],
                    ],
                ]
            )
            ->assertCreated();

        $missingResultUuid = $missingResponse
            ->json('data.uuid');

        $this->assertIsString(
            $missingResultUuid
        );

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$missingResultUuid
                .'/submit'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'results_data',
            ]);

        $duplicateOrder = $this
            ->createProcessingOrder(
                $context['tenant'],
                $context['headers']
            );

        $duplicateItem = $duplicateOrder
            ->items
            ->firstOrFail();

        $this->startOrderItem(
            $context['headers'],
            $duplicateItem
        )->assertOk();

        $duplicateResponse = $this
            ->createDraftResult(
                $context['headers'],
                $duplicateItem,
                [
                    'results_data' => [
                        [
                            'name' => 'Haemoglobin',
                            'value' => '13.1',
                        ],
                        [
                            'name' => 'haemoglobin',
                            'value' => '13.2',
                        ],
                    ],
                ]
            )
            ->assertCreated();

        $duplicateResultUuid = $duplicateResponse
            ->json('data.uuid');

        $this->assertIsString(
            $duplicateResultUuid
        );

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$duplicateResultUuid
                .'/submit'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'results_data',
            ]);

        $workflowOrder = $this
            ->createProcessingOrder(
                $context['tenant'],
                $context['headers']
            );

        $workflowItem = $workflowOrder
            ->items
            ->firstOrFail();

        $this->startOrderItem(
            $context['headers'],
            $workflowItem
        )->assertOk();

        $workflowResponse = $this
            ->createDraftResult(
                $context['headers'],
                $workflowItem
            )
            ->assertCreated();

        $workflowResultUuid = $workflowResponse
            ->json('data.uuid');

        $this->assertIsString(
            $workflowResultUuid
        );

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$workflowResultUuid
                .'/approve'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'test_result',
            ]);

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$workflowResultUuid
                .'/reject',
                [
                    'rejection_reason' => (
                        'Draft results cannot be rejected.'
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
                .$workflowResultUuid
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
                .$workflowResultUuid
                .'/reject',
                []
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'rejection_reason',
            ]);

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$workflowResultUuid
                .'/approve'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'approved'
            );

        $this
            ->withHeaders($context['headers'])
            ->putJson(
                '/api/test-results/'
                .$workflowResultUuid,
                [
                    'notes' => (
                        'Approved results are immutable.'
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
                .$workflowResultUuid
                .'/submit'
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'test_result',
            ]);

        $this
            ->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'
                .$workflowResultUuid
                .'/reject',
                [
                    'rejection_reason' => (
                        'Approved results are terminal.'
                    ),
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'test_result',
            ]);
    }
}
