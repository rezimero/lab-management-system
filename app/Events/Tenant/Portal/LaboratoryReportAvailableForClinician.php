<?php

namespace App\Events\Tenant\Portal;

use Illuminate\Foundation\Events\Dispatchable;

class LaboratoryReportAvailableForClinician
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $reportUuid,
        public readonly string $orderUuid,
        public readonly string $accountUuid
    ) {}
}
