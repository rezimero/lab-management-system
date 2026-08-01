<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Models\LaboratoryReport;
use App\Models\TestResult;
use App\Services\Tenant\TestResult\CanonicalClinicalPayloadHasher;
use App\Services\Tenant\TestResult\TestResultEffectiveValueService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class LaboratoryReportAmendmentPayloadBuilder
{
    /**
     * @var array<int, string>
     */
    private const PATIENT_FIELDS = [
        'medical_record_number',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'email',
        'phone',
        'address',
    ];

    /**
     * @var array<int, string>
     */
    private const ORDER_FIELDS = [
        'priority',
        'referring_clinician',
        'clinical_notes',
        'sample_collected_at',
    ];

    public function __construct(
        private readonly TestResultEffectiveValueService $effectiveValueService,
        private readonly CanonicalClinicalPayloadHasher $hasher
    ) {}

    /**
     * Build explicit proposed snapshots and an auditable change summary.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     patient_snapshot: array<string, mixed>,
     *     order_snapshot: array<string, mixed>,
     *     results_snapshot: array<string, mixed>,
     *     change_summary: array<string, mixed>
     * }
     */
    public function build(
        LaboratoryReport $sourceReport,
        array $input
    ): array {
        $patientSnapshot = $this->snapshotArray(
            $sourceReport->patient_snapshot,
            'patient'
        );
        $orderSnapshot = $this->snapshotArray(
            $sourceReport->order_snapshot,
            'order'
        );
        $resultsSnapshot = $this->snapshotArray(
            $sourceReport->results_snapshot,
            'results'
        );

        $summary = [
            'patient' => [],
            'order' => [],
            'results' => [],
        ];

        $patientCorrections = $input['patient'] ?? [];

        if (is_array($patientCorrections)) {
            $this->applyPatientCorrections(
                $patientSnapshot,
                $patientCorrections,
                $summary['patient']
            );
        }

        $orderCorrections = $input['order'] ?? [];

        if (is_array($orderCorrections)) {
            $this->applyOrderCorrections(
                $orderSnapshot,
                $orderCorrections,
                $summary['order']
            );
        }

        $resultCorrections = $input['results'] ?? [];

        if (is_array($resultCorrections)) {
            $this->applyResultCorrections(
                $sourceReport,
                $resultsSnapshot,
                $resultCorrections,
                $summary['results']
            );
        }

        if (
            $summary['patient'] === []
            && $summary['order'] === []
            && $summary['results'] === []
        ) {
            throw ValidationException::withMessages([
                'amendment' => [
                    'At least one explicit report value must actually change.',
                ],
            ]);
        }

        return [
            'patient_snapshot' => $patientSnapshot,
            'order_snapshot' => $orderSnapshot,
            'results_snapshot' => $resultsSnapshot,
            'change_summary' => $summary,
        ];
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $corrections
     * @param  array<int, array<string, mixed>>  $summary
     */
    private function applyPatientCorrections(
        array &$snapshot,
        array $corrections,
        array &$summary
    ): void {
        foreach (self::PATIENT_FIELDS as $field) {
            if (! array_key_exists($field, $corrections)) {
                continue;
            }

            $value = $corrections[$field];

            if ($field === 'date_of_birth' && $value !== null) {
                $value = Carbon::parse((string) $value)
                    ->toDateString();
            }

            $this->recordScalarChange(
                $snapshot,
                $summary,
                $field,
                $value
            );
        }

        if (
            array_key_exists('first_name', $corrections)
            || array_key_exists('last_name', $corrections)
        ) {
            $fullName = trim(
                implode(
                    ' ',
                    array_filter(
                        [
                            $snapshot['first_name'] ?? null,
                            $snapshot['last_name'] ?? null,
                        ],
                        static fn (mixed $value): bool => (
                            is_string($value)
                            && $value !== ''
                        )
                    )
                )
            );

            $snapshot['full_name'] = $fullName;
        }
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<string, mixed>  $corrections
     * @param  array<int, array<string, mixed>>  $summary
     */
    private function applyOrderCorrections(
        array &$snapshot,
        array $corrections,
        array &$summary
    ): void {
        foreach (self::ORDER_FIELDS as $field) {
            if (! array_key_exists($field, $corrections)) {
                continue;
            }

            $value = $corrections[$field];

            if (
                $field === 'sample_collected_at'
                && $value !== null
            ) {
                $value = Carbon::parse((string) $value)
                    ->toISOString();
            }

            $this->recordScalarChange(
                $snapshot,
                $summary,
                $field,
                $value
            );
        }
    }

    /**
     * @param  array<string, mixed>  $resultsSnapshot
     * @param  array<int, array<string, mixed>>  $corrections
     * @param  array<int, array<string, mixed>>  $summary
     */
    private function applyResultCorrections(
        LaboratoryReport $sourceReport,
        array &$resultsSnapshot,
        array $corrections,
        array &$summary
    ): void {
        $items = $resultsSnapshot['items'] ?? null;

        if (! is_array($items)) {
            throw ValidationException::withMessages([
                'results' => [
                    'The source report does not contain a valid result snapshot.',
                ],
            ]);
        }

        $itemIndexes = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            $resultUuid = $item['result_uuid'] ?? null;

            if (is_string($resultUuid) && $resultUuid !== '') {
                $itemIndexes[$resultUuid] = $index;
            }
        }

        foreach ($corrections as $correctionIndex => $correction) {
            if (! is_array($correction)) {
                continue;
            }

            $resultUuid = $correction['result_uuid'] ?? null;

            if (
                ! is_string($resultUuid)
                || ! array_key_exists($resultUuid, $itemIndexes)
            ) {
                throw ValidationException::withMessages([
                    'results.'.$correctionIndex.'.result_uuid' => [
                        'The result does not belong to the selected source report.',
                    ],
                ]);
            }

            $itemIndex = $itemIndexes[$resultUuid];
            $sourceItem = $items[$itemIndex];

            if (! is_array($sourceItem)) {
                throw ValidationException::withMessages([
                    'results.'.$correctionIndex => [
                        'The source result snapshot is invalid.',
                    ],
                ]);
            }

            $result = TestResult::query()
                ->with('orderItem')
                ->where('uuid', $resultUuid)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertResultBelongsToSource(
                $sourceReport,
                $sourceItem,
                $result,
                $correctionIndex
            );

            $effective = $this->effectiveValueService
                ->resolve($result, true);

            $this->assertSnapshotMatchesEffectiveResult(
                $sourceItem,
                $effective,
                $correctionIndex
            );

            $hasResultsCorrection = array_key_exists(
                'results_data',
                $correction
            );

            $correctedResults = $hasResultsCorrection
                ? $this->normalizeResultsData(
                    (array) $correction['results_data'],
                    $sourceItem,
                    $correctionIndex
                )
                : $effective['results_data'];

            $comparableEffectiveResults = $hasResultsCorrection
                ? $this->normalizeResultsData(
                    $effective['results_data'],
                    $sourceItem,
                    $correctionIndex
                )
                : $effective['results_data'];

            $correctedNotes = array_key_exists(
                'notes',
                $correction
            )
                ? $this->nullableText(
                    $correction['notes']
                )
                : $effective['notes'];

            $changedFields = [];
            $before = [];
            $after = [];

            if (
                ! $this->valuesEqual(
                    $comparableEffectiveResults,
                    $correctedResults
                )
            ) {
                $changedFields[] = 'results_data';
                $before['results_data'] = (
                    $effective['results_data']
                );
                $after['results_data'] = $correctedResults;
            }

            if ($effective['notes'] !== $correctedNotes) {
                $changedFields[] = 'notes';
                $before['notes'] = $effective['notes'];
                $after['notes'] = $correctedNotes;
            }

            if ($changedFields === []) {
                throw ValidationException::withMessages([
                    'results.'.$correctionIndex => [
                        'The proposed result correction is identical to the effective result.',
                    ],
                ]);
            }

            $targetVersion = (
                $effective['effective_version'] + 1
            );

            $sourceItem['results_data'] = $correctedResults;
            $sourceItem['notes'] = $correctedNotes;
            $sourceItem['effective_version'] = $targetVersion;
            $sourceItem['result_amendment_uuid'] = null;

            $items[$itemIndex] = $sourceItem;

            $summary[] = [
                'result_uuid' => $resultUuid,
                'order_item_uuid' => (
                    $sourceItem['order_item_uuid']
                    ?? null
                ),
                'changed_fields' => $changedFields,
                'before' => $before,
                'after' => $after,
                'source_effective_version' => (
                    $effective['effective_version']
                ),
                'target_effective_version' => $targetVersion,
                'source_payload_hash' => (
                    $effective['source_payload_hash']
                ),
            ];
        }

        $resultsSnapshot['items'] = array_values($items);
    }

    /**
     * @param  array<string, mixed>  $sourceItem
     * @param  array<string, mixed>  $effective
     */
    private function assertSnapshotMatchesEffectiveResult(
        array $sourceItem,
        array $effective,
        int $correctionIndex
    ): void {
        $snapshotVersion = (int) (
            $sourceItem['effective_version']
            ?? 1
        );

        if (
            $snapshotVersion
            !== $effective['effective_version']
            || ! $this->valuesEqual(
                $sourceItem['results_data'] ?? [],
                $effective['results_data']
            )
            || ($sourceItem['notes'] ?? null)
                !== $effective['notes']
        ) {
            throw ValidationException::withMessages([
                'results.'.$correctionIndex => [
                    'The source report no longer matches the effective result. Refresh the current report lineage.',
                ],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $sourceItem
     */
    private function assertResultBelongsToSource(
        LaboratoryReport $sourceReport,
        array $sourceItem,
        TestResult $result,
        int $correctionIndex
    ): void {
        if ($result->status !== TestResult::STATUS_APPROVED) {
            throw ValidationException::withMessages([
                'results.'.$correctionIndex => [
                    'Only an approved test result can be amended.',
                ],
            ]);
        }

        $orderItem = $result->orderItem;

        if (
            $orderItem === null
            || $orderItem->laboratory_order_id
                !== $sourceReport->laboratory_order_id
            || $orderItem->uuid
                !== ($sourceItem['order_item_uuid'] ?? null)
        ) {
            throw ValidationException::withMessages([
                'results.'.$correctionIndex => [
                    'The result does not belong to the source report order item.',
                ],
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<string, mixed>  $sourceItem
     * @return array<int, array<string, mixed>>
     */
    private function normalizeResultsData(
        array $rows,
        array $sourceItem,
        int $correctionIndex
    ): array {
        $sourceNames = $this->sourceParameterNames(
            $sourceItem
        );
        $normalizedByName = [];

        foreach ($rows as $rowIndex => $row) {
            if (! is_array($row)) {
                throw ValidationException::withMessages([
                    'results.'.$correctionIndex
                    .'.results_data.'.$rowIndex => [
                        'Each corrected result parameter must be an object.',
                    ],
                ]);
            }

            $name = trim((string) ($row['name'] ?? ''));

            if (
                $name === ''
                || ! in_array($name, $sourceNames, true)
            ) {
                throw ValidationException::withMessages([
                    'results.'.$correctionIndex
                    .'.results_data.'.$rowIndex.'.name' => [
                        'Corrected parameter names must match the source test definition.',
                    ],
                ]);
            }

            if (array_key_exists($name, $normalizedByName)) {
                throw ValidationException::withMessages([
                    'results.'.$correctionIndex
                    .'.results_data.'.$rowIndex.'.name' => [
                        'A corrected result parameter cannot be repeated.',
                    ],
                ]);
            }

            $normalizedByName[$name] = [
                'name' => $name,
                'value' => $this->nullableText(
                    $row['value'] ?? null
                ),
                'unit' => $this->nullableText(
                    $row['unit'] ?? null
                ),
                'reference_range' => $this->nullableText(
                    $row['reference_range'] ?? null
                ),
                'flag' => $this->nullableText(
                    $row['flag'] ?? null
                ),
                'notes' => $this->nullableText(
                    $row['notes'] ?? null
                ),
            ];
        }

        $proposedNames = array_keys($normalizedByName);
        sort($proposedNames, SORT_STRING);

        $expectedNames = $sourceNames;
        sort($expectedNames, SORT_STRING);

        if ($proposedNames !== $expectedNames) {
            throw ValidationException::withMessages([
                'results.'.$correctionIndex.'.results_data' => [
                    'The complete source parameter set must be preserved.',
                ],
            ]);
        }

        return array_map(
            static fn (string $name): array => (
                $normalizedByName[$name]
            ),
            $sourceNames
        );
    }

    /**
     * @param  array<string, mixed>  $sourceItem
     * @return array<int, string>
     */
    private function sourceParameterNames(
        array $sourceItem
    ): array {
        $definitions = $sourceItem['parameters'] ?? [];
        $names = [];

        if (is_array($definitions)) {
            foreach ($definitions as $definition) {
                if (! is_array($definition)) {
                    continue;
                }

                $name = $definition['name'] ?? null;

                if (is_string($name) && $name !== '') {
                    $names[] = $name;
                }
            }
        }

        if ($names === []) {
            $currentRows = $sourceItem['results_data'] ?? [];

            if (is_array($currentRows)) {
                foreach ($currentRows as $row) {
                    if (! is_array($row)) {
                        continue;
                    }

                    $name = $row['name'] ?? null;

                    if (is_string($name) && $name !== '') {
                        $names[] = $name;
                    }
                }
            }
        }

        $names = array_values(array_unique($names));

        if ($names === []) {
            throw ValidationException::withMessages([
                'results' => [
                    'The source test definition has no amendable parameters.',
                ],
            ]);
        }

        return $names;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @param  array<int, array<string, mixed>>  $summary
     */
    private function recordScalarChange(
        array &$snapshot,
        array &$summary,
        string $field,
        mixed $value
    ): void {
        $before = $snapshot[$field] ?? null;

        if ($before === $value) {
            return;
        }

        $snapshot[$field] = $value;

        $summary[] = [
            'field' => $field,
            'before' => $before,
            'after' => $value,
        ];
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }

    private function valuesEqual(
        mixed $first,
        mixed $second
    ): bool {
        return $this->hasher->encode($first)
            === $this->hasher->encode($second);
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotArray(
        mixed $value,
        string $name
    ): array {
        if (is_array($value)) {
            return $value;
        }

        throw ValidationException::withMessages([
            'laboratory_report' => [
                sprintf(
                    'The source report %s snapshot is invalid.',
                    $name
                ),
            ],
        ]);
    }
}
