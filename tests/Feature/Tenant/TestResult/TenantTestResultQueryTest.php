<?php

namespace Tests\Feature\Tenant\TestResult;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\Tenant\TestResultManagementTestCase;

class TenantTestResultQueryTest extends TestResultManagementTestCase
{
    public function test_results_can_be_queried_filtered_and_summarized(): void
    {
        $context = $this
            ->authenticatedTestResultContext(
                'result-query'
            );

        $firstOrder = $this->createProcessingOrder(
            $context['tenant'],
            $context['headers']
        );

        $firstItem = $firstOrder
            ->items
            ->firstOrFail();

        $this->startOrderItem(
            $context['headers'],
            $firstItem
        )->assertOk();

        $firstResponse = $this
            ->createDraftResult(
                $context['headers'],
                $firstItem,
                [
                    'results_data' => [
                        [
                            'name' => 'Haemoglobin',
                            'value' => '13.5',
                        ],
                        [
                            'name' => (
                                'White Blood Cell Count'
                            ),
                            'value' => '7.2',
                        ],
                    ],
                    'notes' => 'First query result.',
                ]
            )
            ->assertCreated();

        $firstResultUuid = $firstResponse->json(
            'data.uuid'
        );

        $this->assertIsString(
            $firstResultUuid
        );

        $secondOrder = $this->createProcessingOrder(
            $context['tenant'],
            $context['headers']
        );

        $secondItem = $secondOrder
            ->items
            ->firstOrFail();

        $this->startOrderItem(
            $context['headers'],
            $secondItem
        )->assertOk();

        $secondResponse = $this
            ->createDraftResult(
                $context['headers'],
                $secondItem,
                [
                    'results_data' => [
                        [
                            'name' => 'Haemoglobin',
                            'value' => '12.8',
                        ],
                        [
                            'name' => (
                                'White Blood Cell Count'
                            ),
                            'value' => '6.9',
                        ],
                    ],
                    'notes' => 'Second query result.',
                ]
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
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'submitted'
            );

        $legacyResultUuid = Str::uuid()
            ->toString();

        $this->runInTenant(
            $context['tenant'],
            static function () use (
                $legacyResultUuid
            ): void {
                DB::connection('tenant')
                    ->table('test_results')
                    ->insert([
                        'uuid' => $legacyResultUuid,
                        'laboratory_order_item_id' => null,
                        'patient_id' => null,
                        'lab_test_id' => null,
                        'user_id' => null,
                        'test_date' => null,
                        'results_data' => json_encode([
                            [
                                'name' => (
                                    'Legacy Parameter'
                                ),
                                'value' => 'Legacy Value',
                            ],
                        ]),
                        'notes' => (
                            'Unlinked legacy result.'
                        ),
                        'status' => 'draft',
                        'completed_at' => null,
                        'entered_by' => null,
                        'submitted_by' => null,
                        'reviewed_by' => null,
                        'submitted_at' => null,
                        'reviewed_at' => null,
                        'rejection_reason' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
        );

        $listResponse = $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/test-results?per_page=10'
            );

        $listResponse
            ->assertOk()
            ->assertJsonCount(
                2,
                'data'
            )
            ->assertJsonPath(
                'meta.total',
                2
            )
            ->assertJsonFragment([
                'uuid' => $firstResultUuid,
            ])
            ->assertJsonFragment([
                'uuid' => $secondResultUuid,
            ])
            ->assertJsonMissing([
                'uuid' => $legacyResultUuid,
            ]);

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/test-results/'
                .$firstResultUuid
            )
            ->assertOk()
            ->assertJsonPath(
                'data.uuid',
                $firstResultUuid
            )
            ->assertJsonPath(
                'data.status',
                'draft'
            )
            ->assertJsonPath(
                'data.order_item.uuid',
                $firstItem->uuid
            )
            ->assertJsonPath(
                'data.notes',
                'First query result.'
            );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/test-results?'
                .http_build_query([
                    'status' => 'submitted',
                    'per_page' => 10,
                ])
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.uuid',
                $secondResultUuid
            )
            ->assertJsonPath(
                'data.0.status',
                'submitted'
            );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/test-results?'
                .http_build_query([
                    'order_uuid' => (
                        $firstOrder->uuid
                    ),
                    'per_page' => 10,
                ])
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.uuid',
                $firstResultUuid
            );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/test-results?'
                .http_build_query([
                    'order_item_uuid' => (
                        $secondItem->uuid
                    ),
                    'per_page' => 10,
                ])
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.uuid',
                $secondResultUuid
            );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/test-results?'
                .http_build_query([
                    'patient_id' => (
                        $firstOrder->patient_id
                    ),
                    'per_page' => 10,
                ])
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.uuid',
                $firstResultUuid
            );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/test-results?'
                .http_build_query([
                    'search' => (
                        $firstOrder->order_number
                    ),
                    'per_page' => 10,
                ])
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.uuid',
                $firstResultUuid
            );

        $today = now()->toDateString();

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/test-results?'
                .http_build_query([
                    'from_date' => $today,
                    'to_date' => $today,
                    'per_page' => 10,
                ])
            )
            ->assertOk()
            ->assertJsonCount(
                2,
                'data'
            )
            ->assertJsonPath(
                'meta.total',
                2
            );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/test-results?'
                .http_build_query([
                    'per_page' => 1,
                    'page' => 1,
                ])
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'meta.total',
                2
            )
            ->assertJsonPath(
                'meta.per_page',
                1
            );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/test-results/stats'
            )
            ->assertOk()
            ->assertJsonPath(
                'total_results',
                2
            )
            ->assertJsonPath(
                'draft',
                1
            )
            ->assertJsonPath(
                'submitted',
                1
            )
            ->assertJsonPath(
                'approved',
                0
            )
            ->assertJsonPath(
                'rejected',
                0
            );
    }
}
