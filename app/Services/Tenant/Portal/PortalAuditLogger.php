<?php

namespace App\Services\Tenant\Portal;

use App\Models\ClinicianPortalAccount;
use App\Models\PatientPortalAccount;
use App\Models\PortalAuditEvent;
use App\Models\PortalInvitation;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;

class PortalAuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function system(
        string $event,
        string $subjectType,
        Model $subject,
        array $metadata = []
    ): PortalAuditEvent {
        return $this->record(
            event: $event,
            actorType: 'system',
            actorId: null,
            actorUuid: null,
            actorSnapshot: null,
            subjectType: $subjectType,
            subject: $subject,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function tenantUser(
        string $event,
        TenantUser $actor,
        string $subjectType,
        Model $subject,
        array $metadata = []
    ): PortalAuditEvent {
        return $this->record(
            event: $event,
            actorType: 'tenant_user',
            actorId: (int) $actor->getKey(),
            actorUuid: null,
            actorSnapshot: [
                'name' => $actor->name,
                'email' => $actor->email,
                'role' => $actor->role,
            ],
            subjectType: $subjectType,
            subject: $subject,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function portalAccount(
        string $event,
        PatientPortalAccount|ClinicianPortalAccount $actor,
        array $metadata = []
    ): PortalAuditEvent {
        $actorType = $actor instanceof PatientPortalAccount
            ? PortalInvitation::ACCOUNT_PATIENT
            : PortalInvitation::ACCOUNT_CLINICIAN;

        return $this->record(
            event: $event,
            actorType: $actorType.'_portal_account',
            actorId: (int) $actor->getKey(),
            actorUuid: $actor->uuid,
            actorSnapshot: [
                'email' => $actor->email,
            ],
            subjectType: $actorType.'_portal_account',
            subject: $actor,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function invitation(
        string $event,
        PortalInvitation $invitation,
        PatientPortalAccount|ClinicianPortalAccount $account,
        array $metadata = []
    ): PortalAuditEvent {
        return $this->record(
            event: $event,
            actorType: 'portal_invitation',
            actorId: (int) $invitation->getKey(),
            actorUuid: $invitation->uuid,
            actorSnapshot: null,
            subjectType: $invitation->account_type
                .'_portal_account',
            subject: $account,
            metadata: $metadata,
        );
    }

    /**
     * @param  array<string, mixed>|null  $actorSnapshot
     * @param  array<string, mixed>  $metadata
     */
    private function record(
        string $event,
        string $actorType,
        ?int $actorId,
        ?string $actorUuid,
        ?array $actorSnapshot,
        string $subjectType,
        Model $subject,
        array $metadata
    ): PortalAuditEvent {
        $subjectUuid = $subject->getAttribute('uuid');

        return PortalAuditEvent::query()->create([
            'event' => $event,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'actor_uuid' => $actorUuid,
            'actor_snapshot' => $actorSnapshot,
            'subject_type' => $subjectType,
            'subject_id' => (int) $subject->getKey(),
            'subject_uuid' => is_string($subjectUuid)
                ? $subjectUuid
                : null,
            'metadata' => $metadata === []
                ? null
                : $metadata,
            'created_at' => now(),
        ]);
    }
}
