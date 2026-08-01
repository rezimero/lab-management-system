<?php

namespace App\Services\Tenant\TestResult;

use JsonException;

class CanonicalClinicalPayloadHasher
{
    /**
     * Hash one effective result payload using the approved canonical format.
     *
     * @param  array<int, array<string, mixed>>  $resultsData
     */
    public function hashEffectivePayload(
        string $resultUuid,
        int $effectiveVersion,
        array $resultsData,
        ?string $notes
    ): string {
        return hash(
            'sha256',
            $this->encode([
                'result_uuid' => $resultUuid,
                'effective_version' => $effectiveVersion,
                'results_data' => $resultsData,
                'notes' => $notes,
            ])
        );
    }

    /**
     * Canonically encode any JSON-compatible value.
     *
     * @throws JsonException
     */
    public function encode(mixed $value): string
    {
        return json_encode(
            $this->canonicalize($value),
            JSON_THROW_ON_ERROR
                | JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
                | JSON_PRESERVE_ZERO_FRACTION
        );
    }

    /**
     * Recursively sort object keys while preserving list order.
     */
    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(
                fn (mixed $item): mixed => (
                    $this->canonicalize($item)
                ),
                $value
            );
        }

        ksort($value, SORT_STRING);

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
