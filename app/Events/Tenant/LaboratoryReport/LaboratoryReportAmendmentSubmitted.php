<?php

namespace App\Events\Tenant\LaboratoryReport;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LaboratoryReportAmendmentSubmitted
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  array<string, mixed>  $requesterSnapshot
     */
    public function __construct(
        public readonly int $tenantId,
        public readonly string $amendmentUuid,
        public readonly string $sourceReportUuid,
        public readonly array $requesterSnapshot
    ) {}
}
