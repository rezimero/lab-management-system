<?php

namespace Tests\Feature\Tenant\TestResult;

use Tests\Support\Tenant\TestResultManagementTestCase;

class TenantTestResultIsolationTest extends TestResultManagementTestCase
{
    public function test_tenant_cannot_access_another_tenants_result_workflow(): void
    {
        $firstContext = $this
            ->authenticatedTestResultContext(
                'result-isolation-first'
            );

        $secondContext = $this
            ->authenticatedTestResultContext(
                'result-isolation-second'
            );

        $secondOrder = $this
            ->createProcessingOrder(
                $secondContext['tenant'],
                $secondContext['headers']
            );

        $secondItem = $secondOrder
            ->items
            ->firstOrFail();

        $this->startOrderItem(
            $secondContext['headers'],
            $secondItem
        )->assertOk();

        $createResponse = $this
            ->createDraftResult(
                $secondContext['headers'],
                $secondItem,
                [
                    'results_data' => [
                        [
                            'name' => 'Haemoglobin',
                            'value' => '13.7',
                        ],
                        [
                            'name' => (
                                'White Blood Cell Count'
                            ),
                            'value' => '7.3',
                        ],
                    ],
                    'notes' => (
                        'Second tenant private result.'
                    ),
                ]
            )
            ->assertCreated();

        $resultUuid = $createResponse->json(
            'data.uuid'
        );

        $this->assertIsString(
            $resultUuid
        );

        $this
            ->withHeaders($firstContext['headers'])
            ->getJson('/api/test-results')
            ->assertOk()
            ->assertJsonCount(
                0,
                'data'
            )
            ->assertJsonPath(
                'meta.total',
                0
            );

        $this
            ->withHeaders($firstContext['headers'])
            ->getJson('/api/test-results/stats')
            ->assertOk()
            ->assertJsonPath(
                'total_results',
                0
            );

        $this->startOrderItem(
            $firstContext['headers'],
            $secondItem
        )->assertNotFound();

        $this
            ->createDraftResult(
                $firstContext['headers'],
                $secondItem
            )
            ->assertNotFound();

        $this
            ->withHeaders($firstContext['headers'])
            ->getJson(
                '/api/test-results/'
                .$resultUuid
            )
            ->assertNotFound();

        $this
            ->withHeaders($firstContext['headers'])
            ->putJson(
                '/api/test-results/'
                .$resultUuid,
                [
                    'notes' => (
                        'Cross-tenant update attempt.'
                    ),
                ]
            )
            ->assertNotFound();

        $this
            ->withHeaders($firstContext['headers'])
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/submit'
            )
            ->assertNotFound();

        $this
            ->withHeaders($firstContext['headers'])
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/approve'
            )
            ->assertNotFound();

        $this
            ->withHeaders($firstContext['headers'])
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/reject',
                [
                    'rejection_reason' => (
                        'Cross-tenant rejection attempt.'
                    ),
                ]
            )
            ->assertNotFound();

        $storedResult = $this->storedTestResult(
            $secondContext['tenant'],
            $resultUuid
        );

        $storedItem = $this->storedOrderItem(
            $secondContext['tenant'],
            $secondItem->uuid
        );

        $this->assertSame(
            'draft',
            $storedResult->status
        );

        $this->assertSame(
            'Second tenant private result.',
            $storedResult->notes
        );

        $this->assertNull(
            $storedResult->submitted_at
        );

        $this->assertNull(
            $storedResult->reviewed_at
        );

        $this->assertSame(
            'in_progress',
            $storedItem->status
        );

        $this->assertNull(
            $storedItem->completed_at
        );

        $this
            ->withHeaders($secondContext['headers'])
            ->getJson(
                '/api/test-results/'
                .$resultUuid
            )
            ->assertOk()
            ->assertJsonPath(
                'data.uuid',
                $resultUuid
            )
            ->assertJsonPath(
                'data.status',
                'draft'
            );
    }
}
