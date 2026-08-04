<?php

namespace App\Services\Tenant\Referral;

use App\Models\LaboratoryOrderItem;
use App\Models\LaboratoryReferral;
use App\Models\LaboratoryReferralEvent;
use App\Models\LaboratoryReferralResult;
use App\Models\Tenant\User as TenantUser;
use App\Models\TestResult;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReferralExternalResultService
{
    private const RELATIONS = [
        'orderItem.order.patient',
        'orderItem.order.collectionBranch',
        'orderItem.order.processingBranch',
        'orderItem.order.priceList',
        'dispatchBranch',
        'referralLaboratory',
        'referralLaboratoryTest.labTest',
        'specimens',
        'latestExternalResult.testResult',
        'externalResults.receiver',
        'externalResults.supersededResult',
        'externalResults.testResult',
        'events.actor',
    ];

    public function __construct(
        private readonly ReferralBranchAccessService $branchAccess,
        private readonly ReferralActorSnapshotFactory $actorSnapshots,
        private readonly ReferralExternalResultPayloadHasher $payloadHasher
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function capture(
        string $uuid,
        array $data,
        TenantUser $actor
    ): LaboratoryReferral {
        return DB::connection('tenant')->transaction(
            function () use ($uuid, $data, $actor): LaboratoryReferral {
                $referral = LaboratoryReferral::query()
                    ->with([
                        'orderItem.order.processingBranch',
                    ])
                    ->where('uuid', $uuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                $item = $referral->orderItem;

                if (! $item instanceof LaboratoryOrderItem) {
                    throw ValidationException::withMessages([
                        'referral' => [
                            'The referred laboratory order item is unavailable.',
                        ],
                    ]);
                }

                $processingBranch = $item->order?->processingBranch;

                if ($processingBranch === null) {
                    throw ValidationException::withMessages([
                        'referral' => [
                            'The referral processing branch is unavailable.',
                        ],
                    ]);
                }

                $this->branchAccess->authorizeMutation(
                    $actor,
                    $processingBranch
                );

                $latest = LaboratoryReferralResult::query()
                    ->where(
                        'laboratory_referral_id',
                        $referral->getKey()
                    )
                    ->orderByDesc('version')
                    ->lockForUpdate()
                    ->first();

                $testResult = TestResult::query()
                    ->where(
                        'laboratory_order_item_id',
                        $item->getKey()
                    )
                    ->lockForUpdate()
                    ->first();

                $this->assertCaptureState(
                    $referral,
                    $latest,
                    $testResult
                );

                $receivedAt = now();
                $reportedAt = $this->reportedAt(
                    $data['reported_at'] ?? null,
                    $receivedAt
                );
                $version = ($latest?->version ?? 0) + 1;
                $resultsData = $this->normalizeResults(
                    $data['results_data'] ?? [],
                    $item->parameters_snapshot ?? []
                );
                $externalReportNumber = $this->nullableText(
                    $data['external_report_number'] ?? null
                );
                $notes = $this->nullableText(
                    $data['notes'] ?? null
                );
                $finalCost = array_key_exists('final_cost', $data)
                    && $data['final_cost'] !== null
                        ? number_format(
                            (float) $data['final_cost'],
                            2,
                            '.',
                            ''
                        )
                        : $referral->final_cost;

                $standardNotes = $this->standardResultNotes(
                    $referral,
                    $externalReportNumber,
                    $notes,
                    $version
                );

                if ($testResult === null) {
                    $testResult = TestResult::query()->create([
                        'laboratory_order_item_id' => $item->getKey(),
                        'results_data' => $resultsData,
                        'notes' => $standardNotes,
                        'status' => TestResult::STATUS_DRAFT,
                        'entered_by' => $actor->getKey(),
                    ]);
                } else {
                    $testResult->forceFill([
                        'results_data' => $resultsData,
                        'notes' => $standardNotes,
                        'status' => TestResult::STATUS_DRAFT,
                        'submitted_by' => null,
                        'submitted_at' => null,
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'rejection_reason' => null,
                    ])->save();
                }

                $reportedAtValue = $reportedAt?->toISOString();
                $hash = $this->payloadHasher->hash(
                    $externalReportNumber,
                    $resultsData,
                    $notes,
                    $reportedAtValue,
                    $finalCost,
                    (string) $referral->currency
                );

                $externalResult = LaboratoryReferralResult::query()->create([
                    'laboratory_referral_id' => $referral->getKey(),
                    'version' => $version,
                    'supersedes_referral_result_id' => $latest?->getKey(),
                    'external_report_number' => $externalReportNumber,
                    'results_data' => $resultsData,
                    'notes' => $notes,
                    'reported_at' => $reportedAt,
                    'received_at' => $receivedAt,
                    'received_by' => $actor->getKey(),
                    'receiver_snapshot' => $this->actorSnapshots->make($actor),
                    'source_payload_hash' => $hash,
                    'test_result_id' => $testResult->getKey(),
                    'created_at' => $receivedAt,
                ]);

                $fromStatus = (string) $referral->status;

                $updates = [
                    'status' => LaboratoryReferral::STATUS_RESULT_RECEIVED,
                    'result_received_by' => $actor->getKey(),
                    'result_received_at' => $receivedAt,
                ];

                if (array_key_exists('final_cost', $data)) {
                    $updates['final_cost'] = $finalCost;
                }

                $referral->forceFill($updates)->save();

                LaboratoryReferralEvent::query()->create([
                    'laboratory_referral_id' => $referral->getKey(),
                    'event_type' => 'external_result_received',
                    'from_status' => $fromStatus,
                    'to_status' => LaboratoryReferral::STATUS_RESULT_RECEIVED,
                    'actor_id' => $actor->getKey(),
                    'actor_snapshot' => $this->actorSnapshots->make($actor),
                    'payload' => [
                        'external_result_uuid' => $externalResult->uuid,
                        'version' => $version,
                        'external_report_number' => $externalReportNumber,
                        'source_payload_hash' => $hash,
                        'test_result_uuid' => $testResult->uuid,
                        'final_cost' => $referral->final_cost,
                        'currency' => $referral->currency,
                    ],
                    'occurred_at' => $receivedAt,
                    'created_at' => $receivedAt,
                ]);

                return $referral->fresh(self::RELATIONS);
            },
            3
        );
    }

    private function assertCaptureState(
        LaboratoryReferral $referral,
        ?LaboratoryReferralResult $latest,
        ?TestResult $testResult
    ): void {
        if ($latest === null) {
            if (! in_array(
                $referral->status,
                [
                    LaboratoryReferral::STATUS_DISPATCHED,
                    LaboratoryReferral::STATUS_ACKNOWLEDGED,
                ],
                true
            )) {
                throw ValidationException::withMessages([
                    'status' => [
                        sprintf(
                            'A %s referral cannot receive an external result.',
                            $referral->status
                        ),
                    ],
                ]);
            }

            if ($testResult !== null) {
                throw ValidationException::withMessages([
                    'test_result' => [
                        'This referred item already has a standard test result.',
                    ],
                ]);
            }

            return;
        }

        if (
            $referral->status
                !== LaboratoryReferral::STATUS_RESULT_RECEIVED
        ) {
            throw ValidationException::withMessages([
                'status' => [
                    sprintf(
                        'A %s referral cannot receive another external result version.',
                        $referral->status
                    ),
                ],
            ]);
        }

        if (
            $testResult === null
            || (int) $latest->test_result_id
                !== (int) $testResult->getKey()
        ) {
            throw ValidationException::withMessages([
                'test_result' => [
                    'The referral result lineage is inconsistent.',
                ],
            ]);
        }

        if (! in_array(
            $testResult->status,
            [
                TestResult::STATUS_DRAFT,
                TestResult::STATUS_REJECTED,
            ],
            true
        )) {
            throw ValidationException::withMessages([
                'test_result' => [
                    sprintf(
                        'A %s standard result blocks another external result version.',
                        $testResult->status
                    ),
                ],
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, mixed>  $parameters
     * @return array<int, array<string, mixed>>
     */
    private function normalizeResults(
        array $rows,
        array $parameters
    ): array {
        $expected = $this->expectedParameterMap($parameters);
        $seen = [];
        $normalized = [];

        foreach ($rows as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $key = Str::lower($name);

            if ($name === '') {
                throw ValidationException::withMessages([
                    "results_data.$index.name" => [
                        'Every external result parameter requires a name.',
                    ],
                ]);
            }

            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    'results_data' => [
                        sprintf(
                            'The external result parameter %s was submitted more than once.',
                            $name
                        ),
                    ],
                ]);
            }

            if ($expected !== [] && ! isset($expected[$key])) {
                throw ValidationException::withMessages([
                    "results_data.$index.name" => [
                        sprintf(
                            'The parameter %s is not part of the ordered test snapshot.',
                            $name
                        ),
                    ],
                ]);
            }

            $seen[$key] = true;
            $definition = $expected[$key] ?? [];

            $normalized[] = [
                'name' => $definition['name'] ?? $name,
                'value' => $this->nullableText(
                    $row['value'] ?? null
                ),
                'unit' => $this->nullableText(
                    $row['unit']
                        ?? $definition['unit']
                        ?? null
                ),
                'reference_range' => $this->nullableText(
                    $row['reference_range']
                        ?? $definition['reference_range']
                        ?? null
                ),
                'flag' => $this->nullableText(
                    $row['flag'] ?? null
                ),
                'notes' => $this->nullableText(
                    $row['notes'] ?? null
                ),
            ];
        }

        return array_values($normalized);
    }

    /**
     * @param  array<int, mixed>  $parameters
     * @return array<string, array<string, mixed>>
     */
    private function expectedParameterMap(array $parameters): array
    {
        $expected = [];

        foreach ($parameters as $parameter) {
            $rawName = is_array($parameter)
                ? (
                    $parameter['name']
                    ?? $parameter['parameter']
                    ?? null
                )
                : $parameter;
            $name = trim((string) $rawName);

            if ($name === '') {
                continue;
            }

            $expected[Str::lower($name)] = [
                'name' => $name,
                'unit' => is_array($parameter)
                    ? ($parameter['unit'] ?? null)
                    : null,
                'reference_range' => is_array($parameter)
                    ? ($parameter['reference_range'] ?? null)
                    : null,
            ];
        }

        return $expected;
    }

    private function reportedAt(
        mixed $value,
        mixed $receivedAt
    ): ?CarbonImmutable {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $reportedAt = CarbonImmutable::parse((string) $value);

        if ($reportedAt->greaterThan(
            CarbonImmutable::instance($receivedAt)->addDay()
        )) {
            throw ValidationException::withMessages([
                'reported_at' => [
                    'The reported time cannot be more than one day after receipt.',
                ],
            ]);
        }

        return $reportedAt;
    }

    private function standardResultNotes(
        LaboratoryReferral $referral,
        ?string $externalReportNumber,
        ?string $notes,
        int $version
    ): string {
        $summary = sprintf(
            'Imported from referral %s, external result version %d.',
            $referral->referral_number,
            $version
        );

        if ($externalReportNumber !== null) {
            $summary .= sprintf(
                ' External report: %s.',
                $externalReportNumber
            );
        }

        if ($notes !== null) {
            $summary .= "\n\n".$notes;
        }

        return $summary;
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
