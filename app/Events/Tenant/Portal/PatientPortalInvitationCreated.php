<?php

namespace App\Events\Tenant\Portal;

use Illuminate\Foundation\Events\Dispatchable;

class PatientPortalInvitationCreated
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $invitationUuid,
        public readonly string $accountUuid,
        public readonly string $recipientEmail
    ) {}
}
