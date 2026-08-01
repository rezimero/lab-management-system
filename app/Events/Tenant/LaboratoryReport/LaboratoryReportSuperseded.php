<?php

namespace App\Events\Tenant\LaboratoryReport;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LaboratoryReportSuperseded
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $sourceReportUuid,
        public readonly string $targetReportUuid
    ) {}
}
