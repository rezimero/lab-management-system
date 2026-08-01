<?php

namespace App\Services\Tenant\TestResult;

use App\Models\TestResult;
use App\Models\TestResultAmendment;

class TestResultEffectiveValueService
{
    public function __construct(
        private readonly CanonicalClinicalPayloadHasher $hasher
    ) {}

    /**
     * Resolve the latest approved effective result.
     *
     * @return array{
     *     effective_version: int,
     *     is_amended: bool,
     *     results_data: array<int, array<string, mixed>>,
     *     notes: string|null,
     *     latest_amendment_uuid: string|null,
     *     latest_amendment_id: int|null,
     *     source_payload_hash: string
     * }
     */
    public function resolve(
        TestResult $result,
        bool $lockLatestAmendment = false
    ): array {
        $query = TestResultAmendment::query()
            ->where('test_result_id', $result->id)
            ->orderByDesc('version');

        if ($lockLatestAmendment) {
            $query->lockForUpdate();
        }

        $amendment = $query->first();

        $effectiveVersion = $amendment?->version ?? 1;
        $resultsData = $amendment?->corrected_results_data
            ?? $result->results_data
            ?? [];
        $notes = $amendment !== null
            ? $amendment->corrected_notes
            : $result->notes;

        return [
            'effective_version' => (int) $effectiveVersion,
            'is_amended' => $amendment !== null,
            'results_data' => $resultsData,
            'notes' => $notes,
            'latest_amendment_uuid' => $amendment?->uuid,
            'latest_amendment_id' => $amendment?->id,
            'source_payload_hash' => (
                $this->hasher->hashEffectivePayload(
                    $result->uuid,
                    (int) $effectiveVersion,
                    $resultsData,
                    $notes
                )
            ),
        ];
    }
}
