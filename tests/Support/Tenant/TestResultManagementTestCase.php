<?php

namespace Tests\Support\Tenant;

use App\Models\LaboratoryOrder;
use App\Models\LaboratoryOrderItem;
use App\Models\Tenant;
use App\Models\TestResult;
use Illuminate\Testing\TestResponse;

abstract class TestResultManagementTestCase extends SpecimenStatusTestCase
{
    /**
     * Register and authenticate a result-management tenant.
     *
     * @return array{
     *     registration: array<string, mixed>,
     *     tenant: Tenant,
     *     headers: array<string, string>
     * }
     */
    protected function authenticatedTestResultContext(
        string $prefix = 'test-result'
    ): array {
        return $this->authenticatedSpecimenContext(
            $prefix
        );
    }

    /**
     * Create an in-progress order with received specimen coverage.
     *
     * @param array<string, string> $headers
     * @param array<int, array<string, mixed>>|null $parameters
     */
    protected function createProcessingOrder(
        Tenant $tenant,
        array $headers,
        int $itemCount = 1,
        ?array $parameters = null
    ): LaboratoryOrder {
        $parameters ??= $this->defaultResultParameters();

        $patient = $this->createPatient(
            $tenant,
            [
                'first_name' => 'Result',
                'last_name' => 'Patient',
            ]
        );

        $items = [];

        for (
            $index = 1;
            $index <= $itemCount;
            $index++
        ) {
            $labTest = $this->createLabTest(
                $tenant,
                [
                    'name' => (
                        'Result Processing Test '
                        .$index
                    ),
                    'category' => 'Haematology',
                    'price' => 2500 * $index,
                    'parameters' => $parameters,
                ]
            );

            $items[] = [
                'lab_test_id' => $labTest->id,
                'test_name_snapshot' => (
                    $labTest->name
                ),
                'test_category_snapshot' => (
                    $labTest->category
                ),
                'price_snapshot' => (
                    $labTest->price
                ),
                'turnaround_time_hours_snapshot' => (
                    $labTest->turnaround_time_hours
                ),
                'parameters_snapshot' => (
                    $labTest->parameters
                ),
                'status' => 'pending',
            ];
        }

        $order = $this->createLaboratoryOrder(
            $tenant,
            $patient,
            [
                'status' => 'pending',
                'sample_collected_at' => null,
            ],
            $items
        );

        $this->createCollectedSpecimen(
            $tenant,
            $order,
            null,
            [
                'status' => 'received',
                'received_at' => now(),
            ]
        );

        $this
            ->withHeaders($headers)
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

        return $this->storedProcessingOrder(
            $tenant,
            $order->uuid
        );
    }

    /**
     * Start processing an order item.
     *
     * @param array<string, string> $headers
     */
    protected function startOrderItem(
        array $headers,
        LaboratoryOrderItem $item
    ): TestResponse {
        return $this
            ->withHeaders($headers)
            ->postJson(
                '/api/laboratory-order-items/'
                .$item->uuid
                .'/start'
            );
    }

    /**
     * Create a draft result through the API.
     *
     * @param array<string, string> $headers
     * @param array<string, mixed>|null $payload
     */
    protected function createDraftResult(
        array $headers,
        LaboratoryOrderItem $item,
        ?array $payload = null
    ): TestResponse {
        return $this
            ->withHeaders($headers)
            ->postJson(
                '/api/laboratory-order-items/'
                .$item->uuid
                .'/test-result',
                $payload ?? (
                    $this->completeResultPayload()
                )
            );
    }

    /**
     * Return a complete result payload.
     *
     * @return array<string, mixed>
     */
    protected function completeResultPayload(): array
    {
        return [
            'results_data' => [
                [
                    'name' => 'Haemoglobin',
                    'value' => '13.5',
                    'unit' => 'g/dL',
                    'reference_range' => (
                        '12.0 - 16.0'
                    ),
                    'flag' => 'normal',
                ],
                [
                    'name' => (
                        'White Blood Cell Count'
                    ),
                    'value' => '7.2',
                    'unit' => '10^9/L',
                    'reference_range' => (
                        '4.0 - 11.0'
                    ),
                    'flag' => 'normal',
                ],
            ],
            'notes' => (
                'Result entered during testing.'
            ),
        ];
    }

    /**
     * Return catalogue parameter definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function defaultResultParameters(): array
    {
        return [
            [
                'name' => 'Haemoglobin',
                'unit' => 'g/dL',
                'reference_range' => (
                    '12.0 - 16.0'
                ),
            ],
            [
                'name' => (
                    'White Blood Cell Count'
                ),
                'unit' => '10^9/L',
                'reference_range' => (
                    '4.0 - 11.0'
                ),
            ],
        ];
    }

    /**
     * Retrieve a result from its tenant database.
     */
    protected function storedTestResult(
        Tenant $tenant,
        string $uuid
    ): TestResult {
        return $this->runInTenant(
            $tenant,
            static fn (): TestResult => (
                TestResult::query()
                    ->with([
                        'orderItem',
                        'enteredBy',
                        'submittedBy',
                        'reviewedBy',
                    ])
                    ->where('uuid', $uuid)
                    ->firstOrFail()
            )
        );
    }

    /**
     * Retrieve an order item from its tenant database.
     */
    protected function storedOrderItem(
        Tenant $tenant,
        string $uuid
    ): LaboratoryOrderItem {
        return $this->runInTenant(
            $tenant,
            static fn (): LaboratoryOrderItem => (
                LaboratoryOrderItem::query()
                    ->with('testResult')
                    ->where('uuid', $uuid)
                    ->firstOrFail()
            )
        );
    }

    /**
     * Retrieve an order and its result-bearing items.
     */
    protected function storedProcessingOrder(
        Tenant $tenant,
        string $uuid
    ): LaboratoryOrder {
        return $this->runInTenant(
            $tenant,
            static fn (): LaboratoryOrder => (
                LaboratoryOrder::query()
                    ->with([
                        'patient',
                        'items' => (
                            static fn ($query) => (
                                $query->orderBy('id')
                            )
                        ),
                        'items.testResult',
                        'items.processingStartedBy',
                    ])
                    ->where('uuid', $uuid)
                    ->firstOrFail()
            )
        );
    }
}
