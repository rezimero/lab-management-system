<?php

namespace App\Services\Tenant\Analyzer;

use App\Models\AnalyzerInstrument;
use App\Models\AnalyzerTestMapping;
use App\Models\LaboratoryReferral;
use App\Models\Specimen;
use App\Models\TestResult;
use App\Support\Tenant\AnalyzerIssueCatalog;
use Illuminate\Support\Str;

class AnalyzerCandidateMatcher
{
    /**
     * @param  array<string, mixed>  $candidate
     * @return array{
     *     analyzer_test_mapping_id: int|null,
     *     specimen_id: int|null,
     *     laboratory_order_item_id: int|null,
     *     normalized_results: array<int, array<string, mixed>>,
     *     mapping_snapshot: array<string, mixed>|null,
     *     validation_issues: array<int, array<string, mixed>>
     * }
     */
    public function resolve(
        AnalyzerInstrument $instrument,
        array $candidate
    ): array {
        $issues = [];
        $normalizedResults = [];
        $mappingSnapshot = null;
        $specimenId = null;
        $orderItemId = null;

        $testCode = strtoupper(
            trim((string) ($candidate['test_code'] ?? ''))
        );

        $testMapping = AnalyzerTestMapping::query()
            ->with([
                'labTest',
                'parameterMappings' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('id'),
            ])
            ->where(
                'analyzer_instrument_id',
                $instrument->getKey()
            )
            ->where('instrument_test_code', $testCode)
            ->where('is_active', true)
            ->first();

        if (! $testMapping instanceof AnalyzerTestMapping) {
            $issues[] = $this->issue(
                AnalyzerIssueCatalog::TEST_UNMAPPED,
                'The analyzer test code is not mapped to an active Rezhel laboratory test.',
                'test_code'
            );

            return $this->resolution(
                null,
                null,
                null,
                [],
                null,
                $issues
            );
        }

        $sampleId = strtoupper(
            trim((string) ($candidate['sample_id'] ?? ''))
        );

        $specimen = Specimen::query()
            ->with([
                'orderItems.order',
                'orderItems.testResult',
            ])
            ->where('accession_number', $sampleId)
            ->first();

        if (! $specimen instanceof Specimen) {
            $issues[] = $this->issue(
                AnalyzerIssueCatalog::SAMPLE_NOT_FOUND,
                'No specimen matches the supplied analyzer sample identifier.',
                'sample_id'
            );

            return $this->resolution(
                $testMapping->getKey(),
                null,
                null,
                [],
                $this->mappingSnapshot($testMapping),
                $issues
            );
        }

        $specimenId = $specimen->getKey();

        if ($specimen->status !== 'received') {
            $issues[] = $this->issue(
                AnalyzerIssueCatalog::SAMPLE_NOT_RECEIVED,
                'The matched specimen is not in received status.',
                'sample_id'
            );
        }

        $matchingItems = $specimen->orderItems
            ->filter(
                fn ($item): bool => (
                    (int) $item->lab_test_id
                    === (int) $testMapping->lab_test_id
                )
            )
            ->values();

        if ($matchingItems->count() === 0) {
            $issues[] = $this->issue(
                AnalyzerIssueCatalog::ORDER_ITEM_NOT_FOUND,
                'The specimen is not linked to an order item for the mapped laboratory test.'
            );
        } elseif ($matchingItems->count() > 1) {
            $issues[] = $this->issue(
                AnalyzerIssueCatalog::ORDER_ITEM_AMBIGUOUS,
                'More than one eligible order item matches the specimen and analyzer test mapping.'
            );
        }

        $item = $matchingItems->count() === 1
            ? $matchingItems->first()
            : null;

        if ($item !== null) {
            $orderItemId = $item->getKey();

            if ($item->order?->status !== 'in_progress') {
                $issues[] = $this->issue(
                    AnalyzerIssueCatalog::ORDER_NOT_IN_PROGRESS,
                    'The matched laboratory order is not in progress.'
                );
            }

            if ($item->status !== 'in_progress') {
                $issues[] = $this->issue(
                    AnalyzerIssueCatalog::ORDER_ITEM_NOT_IN_PROGRESS,
                    'The matched laboratory order item is not in progress.'
                );
            }

            if (
                (int) $item->order?->processing_branch_id
                !== (int) $instrument->branch_id
            ) {
                $issues[] = $this->issue(
                    AnalyzerIssueCatalog::BRANCH_MISMATCH,
                    'The analyzer branch does not match the order processing branch.'
                );
            }

            $hasOpenReferral = LaboratoryReferral::query()
                ->where(
                    'laboratory_order_item_id',
                    $item->getKey()
                )
                ->open()
                ->exists();

            if ($hasOpenReferral) {
                $issues[] = $this->issue(
                    AnalyzerIssueCatalog::OPEN_REFERRAL,
                    'The matched order item has an active laboratory referral.'
                );
            }

            if (
                $item->testResult instanceof TestResult
                || TestResult::query()
                    ->where(
                        'laboratory_order_item_id',
                        $item->getKey()
                    )
                    ->exists()
            ) {
                $issues[] = $this->issue(
                    AnalyzerIssueCatalog::RESULT_CONFLICT,
                    'The matched order item already has a test result.'
                );
            }

            [$normalizedResults, $parameterIssues] = (
                $this->normalizeObservations(
                    $testMapping,
                    $candidate['observations'] ?? [],
                    $item->parameters_snapshot ?? []
                )
            );

            array_push($issues, ...$parameterIssues);
        }

        $mappingSnapshot = $this->mappingSnapshot(
            $testMapping,
            $normalizedResults
        );

        return $this->resolution(
            $testMapping->getKey(),
            $specimenId,
            $orderItemId,
            $normalizedResults,
            $mappingSnapshot,
            $issues
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $observations
     * @param  array<int, mixed>  $orderParameters
     * @return array{
     *     0: array<int, array<string, mixed>>,
     *     1: array<int, array<string, mixed>>
     * }
     */
    private function normalizeObservations(
        AnalyzerTestMapping $testMapping,
        array $observations,
        array $orderParameters
    ): array {
        $issues = [];
        $normalized = [];
        $seenInstrumentCodes = [];
        $seenTargets = [];

        $mappings = $testMapping->parameterMappings
            ->keyBy(
                fn ($mapping): string => strtoupper(
                    trim((string) $mapping->instrument_parameter_code)
                )
            );

        foreach ($observations as $observation) {
            $instrumentCode = strtoupper(
                trim((string) ($observation['code'] ?? ''))
            );

            if (isset($seenInstrumentCodes[$instrumentCode])) {
                $issues[] = $this->issue(
                    AnalyzerIssueCatalog::PARAMETER_DUPLICATE,
                    'The analyzer payload contains a duplicate observation code.',
                    $instrumentCode
                );
                continue;
            }

            $seenInstrumentCodes[$instrumentCode] = true;
            $mapping = $mappings->get($instrumentCode);

            if ($mapping === null) {
                $issues[] = $this->issue(
                    AnalyzerIssueCatalog::PARAMETER_UNMAPPED,
                    'An analyzer observation code is not mapped.',
                    $instrumentCode
                );
                continue;
            }

            $targetKey = Str::lower(
                trim((string) $mapping->target_parameter_name)
            );

            if (isset($seenTargets[$targetKey])) {
                $issues[] = $this->issue(
                    AnalyzerIssueCatalog::PARAMETER_DUPLICATE,
                    'More than one analyzer observation maps to the same Rezhel parameter.',
                    $mapping->target_parameter_name
                );
                continue;
            }

            $seenTargets[$targetKey] = true;

            $value = trim(
                (string) ($observation['value'] ?? '')
            );

            if ($value === '') {
                $issues[] = $this->issue(
                    AnalyzerIssueCatalog::VALUE_MISSING,
                    'The analyzer observation value is empty.',
                    $instrumentCode
                );
            }

            $observedUnit = $this->nullableString(
                $observation['unit'] ?? null
            );
            $expectedUnit = $this->nullableString(
                $mapping->expected_unit
            );

            if (
                $expectedUnit !== null
                && (
                    $observedUnit === null
                    || strcasecmp(
                        $observedUnit,
                        $expectedUnit
                    ) !== 0
                )
            ) {
                $issues[] = $this->issue(
                    AnalyzerIssueCatalog::UNIT_MISMATCH,
                    'The analyzer observation unit does not match the configured mapping.',
                    $instrumentCode,
                    [
                        'expected_unit' => $expectedUnit,
                        'observed_unit' => $observedUnit,
                    ]
                );
            }

            [$flag, $flagSupported] = $this->canonicalFlag(
                $observation['flag'] ?? null
            );

            if (! $flagSupported) {
                $issues[] = $this->issue(
                    AnalyzerIssueCatalog::FLAG_UNSUPPORTED,
                    'The analyzer observation flag is not supported by the canonical result contract.',
                    $instrumentCode
                );
            }

            $normalized[] = [
                'name' => $mapping->target_parameter_name,
                'value' => $value === '' ? null : $value,
                'unit' => $observedUnit ?? $expectedUnit,
                'reference_range' => $this->nullableString(
                    $observation['reference_range'] ?? null
                ),
                'flag' => $flag,
                'notes' => $this->nullableString(
                    $observation['notes'] ?? null
                ),
            ];
        }

        $expected = $this->expectedParameters($orderParameters);

        foreach ($expected as $key => $displayName) {
            if (! isset($seenTargets[$key])) {
                $issues[] = $this->issue(
                    AnalyzerIssueCatalog::PARAMETER_MISSING,
                    'A parameter required by the order snapshot is missing from the analyzer payload.',
                    $displayName
                );
            }
        }

        if ($expected !== []) {
            foreach ($seenTargets as $key => $present) {
                if ($present && ! array_key_exists($key, $expected)) {
                    $issues[] = $this->issue(
                        AnalyzerIssueCatalog::MAPPING_STALE,
                        'An analyzer mapping targets a parameter that is not present in the historical order snapshot.',
                        $key
                    );
                }
            }
        }

        return [$normalized, $issues];
    }

    /**
     * @param  array<int, mixed>  $parameters
     * @return array<string, string>
     */
    private function expectedParameters(array $parameters): array
    {
        $expected = [];

        foreach ($parameters as $parameter) {
            $name = is_array($parameter)
                ? (
                    $parameter['name']
                    ?? $parameter['parameter']
                    ?? null
                )
                : $parameter;

            $displayName = trim((string) $name);

            if ($displayName === '') {
                continue;
            }

            $expected[Str::lower($displayName)] = $displayName;
        }

        return $expected;
    }

    /**
     * @return array{0: string|null, 1: bool}
     */
    private function canonicalFlag(mixed $value): array
    {
        $value = strtoupper(trim((string) ($value ?? '')));

        if ($value === '') {
            return [null, true];
        }

        return match ($value) {
            'N', 'NORMAL' => ['normal', true],
            'L', 'LOW' => ['low', true],
            'H', 'HIGH' => ['high', true],
            'C', 'CRITICAL' => ['critical', true],
            'A', 'ABNORMAL' => ['abnormal', true],
            default => [null, false],
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $normalizedResults
     * @return array<string, mixed>
     */
    private function mappingSnapshot(
        AnalyzerTestMapping $testMapping,
        array $normalizedResults = []
    ): array {
        return [
            'test_mapping_uuid' => $testMapping->uuid,
            'instrument_test_code' => (
                $testMapping->instrument_test_code
            ),
            'lab_test_uuid' => $testMapping->labTest?->uuid,
            'lab_test_name' => $testMapping->labTest?->name,
            'parameters' => $testMapping->parameterMappings
                ->map(
                    fn ($mapping): array => [
                        'mapping_uuid' => $mapping->uuid,
                        'instrument_parameter_code' => (
                            $mapping->instrument_parameter_code
                        ),
                        'target_parameter_name' => (
                            $mapping->target_parameter_name
                        ),
                        'expected_unit' => $mapping->expected_unit,
                    ]
                )
                ->values()
                ->all(),
            'normalized_parameter_names' => collect($normalizedResults)
                ->pluck('name')
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $normalizedResults
     * @param  array<string, mixed>|null  $mappingSnapshot
     * @param  array<int, array<string, mixed>>  $issues
     * @return array<string, mixed>
     */
    private function resolution(
        ?int $testMappingId,
        ?int $specimenId,
        ?int $orderItemId,
        array $normalizedResults,
        ?array $mappingSnapshot,
        array $issues
    ): array {
        return [
            'analyzer_test_mapping_id' => $testMappingId,
            'specimen_id' => $specimenId,
            'laboratory_order_item_id' => $orderItemId,
            'normalized_results' => $normalizedResults,
            'mapping_snapshot' => $mappingSnapshot,
            'validation_issues' => $issues,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function issue(
        string $code,
        string $message,
        ?string $field = null,
        array $context = []
    ): array {
        return array_filter([
            'code' => $code,
            'message' => $message,
            'field' => $field,
            'context' => $context === [] ? null : $context,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
