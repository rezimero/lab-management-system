<?php

namespace App\Events\Tenant\Portal;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class LaboratoryReportAvailableForClinician implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $reportUuid,
        public readonly string $orderUuid,
        public readonly string $accountUuid
    ) {}
}
