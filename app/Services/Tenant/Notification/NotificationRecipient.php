<?php

namespace App\Services\Tenant\Notification;

use App\Models\ClinicianPortalAccount;
use App\Models\NotificationMessage;
use App\Models\PatientPortalAccount;
use App\Models\Tenant\User as TenantUser;

final readonly class NotificationRecipient
{
    public function __construct(
        public string $type,
        public int $id,
        public ?string $uuid,
        public ?string $email
    ) {}

    public static function tenantUser(
        TenantUser $user
    ): self {
        return new self(
            NotificationMessage::RECIPIENT_TENANT_USER,
            (int) $user->getKey(),
            null,
            self::normalizeEmail($user->email)
        );
    }

    public static function patientPortal(
        PatientPortalAccount $account
    ): self {
        return new self(
            NotificationMessage::RECIPIENT_PATIENT_PORTAL,
            (int) $account->getKey(),
            $account->uuid,
            self::normalizeEmail($account->email)
        );
    }

    public static function clinicianPortal(
        ClinicianPortalAccount $account
    ): self {
        return new self(
            NotificationMessage::RECIPIENT_CLINICIAN_PORTAL,
            (int) $account->getKey(),
            $account->uuid,
            self::normalizeEmail($account->email)
        );
    }

    private static function normalizeEmail(
        mixed $email
    ): ?string {
        $normalized = strtolower(trim((string) $email));

        return filter_var(
            $normalized,
            FILTER_VALIDATE_EMAIL
        ) !== false
            ? $normalized
            : null;
    }
}
