<?php

namespace App\Services\Tenant\Referral;

use App\Services\Tenant\TestResult\CanonicalClinicalPayloadHasher;

class ReferralExternalResultPayloadHasher
{
    public function __construct(
        private readonly CanonicalClinicalPayloadHasher $canonical
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $resultsData
     */
    public function hash(
        ?string $externalReportNumber,
        array $resultsData,
        ?string $notes,
        ?string $reportedAt,
        ?string $finalCost,
        string $currency
    ): string {
        return hash(
            'sha256',
            $this->canonical->encode([
                'external_report_number' => $externalReportNumber,
                'results_data' => $resultsData,
                'notes' => $notes,
                'reported_at' => $reportedAt,
                'final_cost' => $finalCost,
                'currency' => $currency,
            ])
        );
    }
}
