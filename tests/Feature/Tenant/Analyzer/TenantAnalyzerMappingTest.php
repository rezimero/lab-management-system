<?php

namespace Tests\Feature\Tenant\Analyzer;

use Tests\Support\Tenant\AnalyzerManagementTestCase;

class TenantAnalyzerMappingTest extends AnalyzerManagementTestCase
{
    public function test_administrator_maps_instrument_test_and_parameters_to_catalogue(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'analyzer-mapping'
        );
        $branch = $this->headOfficeBranch($context['tenant']);

        $labTest = $this->createLabTest($context['tenant'], [
            'name' => 'Full Blood Count',
            'category' => 'Haematology',
            'parameters' => [
                [
                    'name' => 'Haemoglobin',
                    'unit' => 'g/dL',
                ],
                [
                    'name' => 'White Blood Cells',
                    'unit' => '10^9/L',
                ],
            ],
        ]);

        $analyzerUuid = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/analyzers', [
                'branch_uuid' => $branch->uuid,
                'code' => 'MAP-01',
                'name' => 'Mapping Analyzer',
            ])
            ->assertCreated()
            ->json('data.uuid');

        $testMapping = $this
            ->withHeaders($context['headers'])
            ->postJson(
                "/api/analyzers/{$analyzerUuid}/test-mappings",
                [
                    'lab_test_uuid' => $labTest->uuid,
                    'instrument_test_code' => 'cbc',
                    'instrument_test_name' => 'Complete Blood Count',
                ]
            );

        $testMapping
            ->assertCreated()
            ->assertJsonPath('data.instrument_test_code', 'CBC')
            ->assertJsonPath('data.lab_test.uuid', $labTest->uuid);

        $testMappingUuid = $testMapping->json('data.uuid');

        $parameter = $this
            ->withHeaders($context['headers'])
            ->postJson(
                "/api/analyzers/{$analyzerUuid}/test-mappings/{$testMappingUuid}/parameter-mappings",
                [
                    'instrument_parameter_code' => 'hgb',
                    'instrument_parameter_name' => 'HGB',
                    'target_parameter_name' => 'haemoglobin',
                ]
            );

        $parameter
            ->assertCreated()
            ->assertJsonPath(
                'data.instrument_parameter_code',
                'HGB'
            )
            ->assertJsonPath(
                'data.target_parameter_name',
                'Haemoglobin'
            )
            ->assertJsonPath(
                'data.expected_unit',
                'g/dL'
            );
    }

    public function test_parameter_mapping_rejects_unknown_catalogue_parameter(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'analyzer-unknown-parameter'
        );
        $branch = $this->headOfficeBranch($context['tenant']);

        $labTest = $this->createLabTest($context['tenant'], [
            'parameters' => [
                [
                    'name' => 'Haemoglobin',
                    'unit' => 'g/dL',
                ],
            ],
        ]);

        $analyzerUuid = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/analyzers', [
                'branch_uuid' => $branch->uuid,
                'code' => 'PARAM-01',
                'name' => 'Parameter Analyzer',
            ])
            ->assertCreated()
            ->json('data.uuid');

        $testMappingUuid = $this
            ->withHeaders($context['headers'])
            ->postJson(
                "/api/analyzers/{$analyzerUuid}/test-mappings",
                [
                    'lab_test_uuid' => $labTest->uuid,
                    'instrument_test_code' => 'TEST-1',
                ]
            )
            ->assertCreated()
            ->json('data.uuid');

        $this->withHeaders($context['headers'])
            ->postJson(
                "/api/analyzers/{$analyzerUuid}/test-mappings/{$testMappingUuid}/parameter-mappings",
                [
                    'instrument_parameter_code' => 'XYZ',
                    'target_parameter_name' => 'Not A Catalogue Parameter',
                ]
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('target_parameter_name');
    }
}
