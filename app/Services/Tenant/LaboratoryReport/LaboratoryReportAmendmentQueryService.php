<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Models\LaboratoryReport;
use App\Models\LaboratoryReportAmendment;
use Illuminate\Database\Eloquent\Collection;

class LaboratoryReportAmendmentQueryService
{
    /**
     * @var array<int, string>
     */
    private const RELATIONS = [
        'sourceReport',
        'targetReport',
        'requestedBy',
        'reviewedBy',
        'resultAmendments.testResult',
    ];

    /**
     * Return every workflow request in one report lineage.
     *
     * @return Collection<int, LaboratoryReportAmendment>
     */
    public function forLineage(
        LaboratoryReport $report
    ): Collection {
        return LaboratoryReportAmendment::query()
            ->with(self::RELATIONS)
            ->whereHas(
                'sourceReport',
                fn ($query) => $query->where(
                    'lineage_uuid',
                    $report->lineage_uuid
                )
            )
            ->orderByDesc('requested_at')
            ->get();
    }

    /**
     * Prepare one exact tenant-local amendment response.
     */
    public function prepare(
        LaboratoryReportAmendment $amendment
    ): LaboratoryReportAmendment {
        return $amendment->loadMissing(
            self::RELATIONS
        );
    }
}
