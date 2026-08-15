<?php

namespace App\Events\Tenant\Portal;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class ClinicianPortalInvitationCreated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $invitationUuid,
        public readonly string $accountUuid,
        public readonly string $recipientEmail
    ) {}
}
