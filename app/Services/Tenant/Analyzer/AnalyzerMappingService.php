<?php

namespace App\Services\Tenant\Analyzer;

use App\Models\AnalyzerInstrument;
use App\Models\AnalyzerParameterMapping;
use App\Models\AnalyzerTestMapping;
use App\Models\LabTest;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnalyzerMappingService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function saveTestMapping(
        AnalyzerInstrument $instrument,
        ?AnalyzerTestMapping $mapping,
        array $data,
        TenantUser $actor
    ): AnalyzerTestMapping {
        return DB::connection('tenant')->transaction(
            function () use (
                $instrument,
                $mapping,
                $data,
                $actor
            ): AnalyzerTestMapping {
                $instrument = AnalyzerInstrument::query()
                    ->lockForUpdate()
                    ->findOrFail($instrument->getKey());

                if (
                    $mapping !== null
                    && (int) $mapping->analyzer_instrument_id
                        !== (int) $instrument->getKey()
                ) {
                    abort(404);
                }

                $labTest = LabTest::query()
                    ->where('uuid', $data['lab_test_uuid'])
                    ->lockForUpdate()
                    ->first();

                if ($labTest === null) {
                    throw ValidationException::withMessages([
                        'lab_test_uuid' => [
                            'The selected laboratory test does not exist.',
                        ],
                    ]);
                }

                $willBeActive = (bool) (
                    $data['is_active']
                    ?? ($mapping?->is_active ?? true)
                );

                if ($willBeActive && ! $instrument->is_active) {
                    throw ValidationException::withMessages([
                        'is_active' => [
                            'An inactive analyzer cannot have an active test mapping.',
                        ],
                    ]);
                }

                if ($willBeActive && ! $labTest->is_active) {
                    throw ValidationException::withMessages([
                        'lab_test_uuid' => [
                            'Select an active laboratory test.',
                        ],
                    ]);
                }

                $mapping ??= new AnalyzerTestMapping;

                $mapping->fill([
                    'analyzer_instrument_id' => $instrument->getKey(),
                    'lab_test_id' => $labTest->getKey(),
                    'instrument_test_code' => strtoupper(trim(
                        (string) $data['instrument_test_code']
                    )),
                    'instrument_test_name' => $this->nullableString(
                        $data['instrument_test_name'] ?? null
                    ),
                    'is_active' => $willBeActive,
                    'updated_by' => $actor->getKey(),
                ]);

                if (! $mapping->exists) {
                    $mapping->created_by = $actor->getKey();
                }

                $mapping->save();

                return $mapping->fresh([
                    'instrument.branch',
                    'labTest',
                    'parameterMappings',
                ]);
            },
            3
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveParameterMapping(
        AnalyzerTestMapping $testMapping,
        ?AnalyzerParameterMapping $mapping,
        array $data,
        TenantUser $actor
    ): AnalyzerParameterMapping {
        return DB::connection('tenant')->transaction(
            function () use (
                $testMapping,
                $mapping,
                $data,
                $actor
            ): AnalyzerParameterMapping {
                $testMapping = AnalyzerTestMapping::query()
                    ->with(['instrument', 'labTest'])
                    ->lockForUpdate()
                    ->findOrFail($testMapping->getKey());

                if (
                    $mapping !== null
                    && (int) $mapping->analyzer_test_mapping_id
                        !== (int) $testMapping->getKey()
                ) {
                    abort(404);
                }

                $willBeActive = (bool) (
                    $data['is_active']
                    ?? ($mapping?->is_active ?? true)
                );

                if (
                    $willBeActive
                    && (
                        ! $testMapping->is_active
                        || ! $testMapping->instrument->is_active
                        || ! $testMapping->labTest->is_active
                    )
                ) {
                    throw ValidationException::withMessages([
                        'is_active' => [
                            'The analyzer, test mapping and local laboratory test must all be active.',
                        ],
                    ]);
                }

                $parameter = $this->resolveTargetParameter(
                    $testMapping->labTest,
                    $data['target_parameter_name']
                );

                $mapping ??= new AnalyzerParameterMapping;

                $mapping->fill([
                    'analyzer_test_mapping_id' => $testMapping->getKey(),
                    'instrument_parameter_code' => strtoupper(trim(
                        (string) $data['instrument_parameter_code']
                    )),
                    'instrument_parameter_name' => $this->nullableString(
                        $data['instrument_parameter_name'] ?? null
                    ),
                    'target_parameter_name' => $parameter['name'],
                    'expected_unit' => array_key_exists(
                        'expected_unit',
                        $data
                    )
                        ? $this->nullableString($data['expected_unit'])
                        : $this->nullableString(
                            $parameter['unit'] ?? null
                        ),
                    'is_required' => (bool) (
                        $data['is_required']
                        ?? ($mapping?->is_required ?? true)
                    ),
                    'is_active' => $willBeActive,
                    'updated_by' => $actor->getKey(),
                ]);

                if (! $mapping->exists) {
                    $mapping->created_by = $actor->getKey();
                }

                $mapping->save();

                return $mapping->fresh('testMapping.labTest');
            },
            3
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveTargetParameter(
        LabTest $labTest,
        string $requestedName
    ): array {
        $requestedName = trim($requestedName);

        foreach ((array) $labTest->parameters as $parameter) {
            if (! is_array($parameter)) {
                continue;
            }

            $name = trim((string) ($parameter['name'] ?? ''));

            if (
                $name !== ''
                && mb_strtolower($name)
                    === mb_strtolower($requestedName)
            ) {
                return [
                    ...$parameter,
                    'name' => $name,
                ];
            }
        }

        throw ValidationException::withMessages([
            'target_parameter_name' => [
                'The target parameter is not defined on the selected laboratory test.',
            ],
        ]);
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
