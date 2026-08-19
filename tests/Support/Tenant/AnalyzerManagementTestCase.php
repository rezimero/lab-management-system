<?php

namespace Tests\Support\Tenant;

use App\Models\Branch;
use App\Models\PlanFeature;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Support\Commercial\CommercialFeatureCatalog;

abstract class AnalyzerManagementTestCase extends TestResultManagementTestCase
{
    /**
     * @var array<int, bool>
     */
    private array $analyzerFeatureOriginalStates = [];

    protected function tearDown(): void
    {
        try {
            foreach (
                $this->analyzerFeatureOriginalStates
                as $featureId => $isEnabled
            ) {
                PlanFeature::query()
                    ->whereKey($featureId)
                    ->update(['is_enabled' => $isEnabled]);
            }
        } finally {
            parent::tearDown();
        }
    }

    /**
     * @return array{
     *     registration: array<string, mixed>,
     *     tenant: Tenant,
     *     headers: array<string, string>
     * }
     */
    protected function authenticatedAnalyzerContext(
        string $prefix = 'analyzer'
    ): array {
        $context = $this->authenticatedTestResultContext($prefix);

        $subscription = TenantSubscription::query()
            ->where('tenant_id', $context['tenant']->getKey())
            ->latest('starts_at')
            ->firstOrFail();

        $feature = PlanFeature::query()
            ->where('plan_id', $subscription->plan_id)
            ->where(
                'feature_key',
                CommercialFeatureCatalog::ANALYZER_INTEGRATION
            )
            ->firstOrFail();

        $this->analyzerFeatureOriginalStates[
            $feature->getKey()
        ] ??= (bool) $feature->is_enabled;

        $feature->update(['is_enabled' => true]);

        return $context;
    }

    protected function headOfficeBranch(
        Tenant $tenant
    ): Branch {
        return $this->runInTenant(
            $tenant,
            static fn (): Branch => Branch::query()
                ->where('is_head_office', true)
                ->firstOrFail()
        );
    }

    /**
     * Build one active analyzer, mapping and in-progress result target.
     *
     * @return array{
     *     analyzer_uuid: string,
     *     machine_token: string,
     *     machine_headers: array<string, string>,
     *     order: \App\Models\LaboratoryOrder,
     *     item: \App\Models\LaboratoryOrderItem,
     *     specimen: \App\Models\Specimen,
     *     test_mapping_uuid: string,
     *     parameter_mappings: array<string, string>
     * }
     */
    protected function configuredAnalyzerResultTarget(
        array $context,
        string $prefix = 'machine'
    ): array {
        $branch = $this->headOfficeBranch($context['tenant']);

        $order = $this->createProcessingOrder(
            $context['tenant'],
            $context['headers']
        );

        $this->runInTenant(
            $context['tenant'],
            function () use (
                $order,
                $branch
            ): void {
                \App\Models\LaboratoryOrder::query()
                    ->whereKey($order->getKey())
                    ->update([
                        'processing_branch_id' => $branch->getKey(),
                    ]);
            }
        );

        $order = $this->storedProcessingOrder(
            $context['tenant'],
            $order->uuid
        );

        $item = $order->items->firstOrFail();

        $this->startOrderItem(
            $context['headers'],
            $item
        )->assertOk();

        $item = $this->storedOrderItem(
            $context['tenant'],
            $item->uuid
        );

        $specimen = $this->runInTenant(
            $context['tenant'],
            fn (): \App\Models\Specimen => (
                \App\Models\Specimen::query()
                    ->where(
                        'laboratory_order_id',
                        $order->getKey()
                    )
                    ->firstOrFail()
            )
        );

        $labTest = $this->runInTenant(
            $context['tenant'],
            fn (): \App\Models\LabTest => (
                \App\Models\LabTest::query()
                    ->findOrFail($item->lab_test_id)
            )
        );

        $response = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/analyzers', [
                'branch_uuid' => $branch->uuid,
                'code' => strtoupper($prefix).'-01',
                'name' => ucfirst($prefix).' Analyzer',
            ])
            ->assertCreated();

        $analyzerUuid = $response->json('data.uuid');
        $machineToken = $response->json('machine_token');

        $testMapping = $this
            ->withHeaders($context['headers'])
            ->postJson(
                "/api/analyzers/{$analyzerUuid}/test-mappings",
                [
                    'lab_test_uuid' => $labTest->uuid,
                    'instrument_test_code' => 'CBC',
                ]
            )
            ->assertCreated();

        $testMappingUuid = $testMapping->json('data.uuid');

        $parameterMappings = [];

        foreach ([
            'HGB' => 'Haemoglobin',
            'WBC' => 'White Blood Cell Count',
        ] as $code => $target) {
            $mapping = $this
                ->withHeaders($context['headers'])
                ->postJson(
                    "/api/analyzers/{$analyzerUuid}/test-mappings/{$testMappingUuid}/parameter-mappings",
                    [
                        'instrument_parameter_code' => $code,
                        'target_parameter_name' => $target,
                    ]
                )
                ->assertCreated();

            $parameterMappings[$code] = $mapping->json('data.uuid');
        }

        return [
            'analyzer_uuid' => $analyzerUuid,
            'machine_token' => $machineToken,
            'machine_headers' => [
                'Accept' => 'application/json',
                'X-Tenant-Domain' => (
                    $context['registration']['domain']
                ),
                'X-Analyzer-Id' => $analyzerUuid,
                'Authorization' => 'Bearer '.$machineToken,
            ],
            'order' => $order,
            'item' => $item,
            'specimen' => $specimen,
            'test_mapping_uuid' => $testMappingUuid,
            'parameter_mappings' => $parameterMappings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function canonicalAnalyzerPayload(
        string $sampleId,
        array $overrides = []
    ): array {
        return array_replace_recursive(
            [
                'schema' => 'rezhel.analyzer-result.v1',
                'message_id' => 'device-message-1',
                'sent_at' => now()->toISOString(),
                'results' => [
                    [
                        'result_key' => $sampleId.':CBC',
                        'sample_id' => $sampleId,
                        'test_code' => 'CBC',
                        'observations' => [
                            [
                                'code' => 'HGB',
                                'name' => 'Haemoglobin',
                                'value' => '13.4',
                                'unit' => 'g/dL',
                                'reference_range' => '12.0 - 16.0',
                                'flag' => 'N',
                            ],
                            [
                                'code' => 'WBC',
                                'name' => 'White Blood Cell Count',
                                'value' => '7.2',
                                'unit' => '10^9/L',
                                'reference_range' => '4.0 - 11.0',
                                'flag' => 'N',
                            ],
                        ],
                    ],
                ],
            ],
            $overrides
        );
    }
}
