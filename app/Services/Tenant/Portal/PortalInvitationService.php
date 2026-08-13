<?php

namespace App\Services\Tenant\Portal;

use App\Events\Tenant\Portal\ClinicianPortalInvitationCreated;
use App\Events\Tenant\Portal\PatientPortalInvitationCreated;
use App\Models\ClinicianPortalAccount;
use App\Models\Patient;
use App\Models\PatientPortalAccount;
use App\Models\PortalAccessToken;
use App\Models\PortalInvitation;
use App\Models\ReferringClinician;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

class PortalInvitationService
{
    public function __construct(
        private readonly PortalAuditLogger $audit
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function issuePatient(
        int $patientId,
        string $email,
        TenantUser $actor
    ): array {
        $patient = Patient::query()
            ->whereKey($patientId)
            ->firstOrFail();

        return $this->issue(
            accountType: PortalInvitation::ACCOUNT_PATIENT,
            subject: $patient,
            email: $email,
            actor: $actor,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function issueClinician(
        string $clinicianUuid,
        string $email,
        TenantUser $actor
    ): array {
        $clinician = ReferringClinician::query()
            ->where('uuid', $clinicianUuid)
            ->firstOrFail();

        return $this->issue(
            accountType: PortalInvitation::ACCOUNT_CLINICIAN,
            subject: $clinician,
            email: $email,
            actor: $actor,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(
        string $plainTextToken
    ): array {
        $invitation = $this->usableInvitation(
            $plainTextToken
        );

        $account = $this->resolveAccount($invitation);
        $this->assertAccountMayActivate($account);

        return [
            'account_type' => $invitation->account_type,
            'email' => $account->email,
            'display_name' => $this->displayName($account),
            'expires_at' => $invitation->expires_at
                ?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function activate(
        string $plainTextToken,
        string $password
    ): array {
        return DB::connection('tenant')->transaction(
            function () use (
                $plainTextToken,
                $password
            ): array {
                $invitation = PortalInvitation::query()
                    ->where(
                        'token_hash',
                        hash('sha256', $plainTextToken)
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $this->isUsable($invitation)) {
                    $this->invalidInvitation();
                }

                $account = $this->resolveAccount(
                    $invitation,
                    true
                );
                $this->assertAccountMayActivate($account);

                $activatedAt = now();

                $account->forceFill([
                    'password' => $password,
                    'email_verified_at' => $activatedAt,
                    'activated_at' => $activatedAt,
                ])->save();

                $invitation->forceFill([
                    'used_at' => $activatedAt,
                ])->save();

                $this->audit->invitation(
                    'portal_invitation_used',
                    $invitation,
                    $account
                );
                $this->audit->invitation(
                    'portal_account_activated',
                    $invitation,
                    $account
                );

                return [
                    'message' => (
                        'Portal account activated successfully.'
                    ),
                    'account_type' => $invitation->account_type,
                    'email' => $account->email,
                ];
            },
            3
        );
    }

    public function revoke(
        string $invitationUuid,
        TenantUser $actor
    ): PortalInvitation {
        return DB::connection('tenant')->transaction(
            function () use (
                $invitationUuid,
                $actor
            ): PortalInvitation {
                $invitation = PortalInvitation::query()
                    ->where('uuid', $invitationUuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $invitation->used_at === null
                    && $invitation->revoked_at === null
                ) {
                    $invitation->forceFill([
                        'revoked_at' => now(),
                    ])->save();

                    $account = $this->resolveAccount(
                        $invitation
                    );

                    $this->audit->tenantUser(
                        'portal_invitation_revoked',
                        $actor,
                        $invitation->account_type
                            .'_portal_account',
                        $account,
                        [
                            'invitation_uuid' => (
                                $invitation->uuid
                            ),
                        ]
                    );
                }

                return $invitation->refresh();
            },
            3
        );
    }

    public function setAccountActive(
        string $accountType,
        string $accountUuid,
        bool $isActive,
        TenantUser $actor
    ): PatientPortalAccount|ClinicianPortalAccount {
        return DB::connection('tenant')->transaction(
            function () use (
                $accountType,
                $accountUuid,
                $isActive,
                $actor
            ): PatientPortalAccount|ClinicianPortalAccount {
                $account = $this->accountQuery(
                    $accountType
                )
                    ->where('uuid', $accountUuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($isActive) {
                    $this->assertLinkedSubjectActive(
                        $account
                    );
                }

                if ((bool) $account->is_active !== $isActive) {
                    $account->forceFill([
                        'is_active' => $isActive,
                    ])->save();

                    if (! $isActive) {
                        PortalAccessToken::query()
                            ->where(
                                'account_type',
                                $accountType
                            )
                            ->where(
                                'account_id',
                                $account->getKey()
                            )
                            ->whereNull('revoked_at')
                            ->update([
                                'revoked_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }

                    $this->audit->tenantUser(
                        $isActive
                            ? 'portal_account_enabled'
                            : 'portal_account_disabled',
                        $actor,
                        $accountType.'_portal_account',
                        $account
                    );
                }

                return $account
                    ->refresh()
                    ->load('latestInvitation');
            },
            3
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function issue(
        string $accountType,
        Model $subject,
        string $email,
        TenantUser $actor
    ): array {
        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            throw new LogicException(
                'No active tenant is available.'
            );
        }

        $email = strtolower(trim($email));

        $issued = DB::connection('tenant')->transaction(
            function () use (
                $accountType,
                $subject,
                $email,
                $actor
            ): array {
                $subject = $subject instanceof Patient
                    ? Patient::query()
                        ->whereKey($subject->getKey())
                        ->lockForUpdate()
                        ->firstOrFail()
                    : ReferringClinician::query()
                        ->whereKey($subject->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                if (! $subject->is_active) {
                    $this->inactiveSubject();
                }

                $foreignKey = $accountType
                    === PortalInvitation::ACCOUNT_PATIENT
                    ? 'patient_id'
                    : 'referring_clinician_id';

                $account = $this->accountQuery(
                    $accountType
                )
                    ->where(
                        $foreignKey,
                        $subject->getKey()
                    )
                    ->lockForUpdate()
                    ->first();

                $created = $account === null;
                $wasInactive = $account !== null
                    && ! $account->is_active;

                if ($account !== null && $account->activated_at !== null) {
                    throw ValidationException::withMessages([
                        'account' => [
                            'This portal account is already activated. Password reset is deferred to the notifications milestone.',
                        ],
                    ]);
                }

                $emailInUse = $this->accountQuery(
                    $accountType
                )
                    ->where('email', $email)
                    ->when(
                        $account !== null,
                        fn ($query) => $query->where(
                            $account->getKeyName(),
                            '!=',
                            $account->getKey()
                        )
                    )
                    ->exists();

                if ($emailInUse) {
                    throw ValidationException::withMessages([
                        'email' => [
                            'This email is already assigned to another portal account.',
                        ],
                    ]);
                }

                if ($account === null) {
                    $account = $this->accountQuery(
                        $accountType
                    )->create([
                        $foreignKey => $subject->getKey(),
                        'email' => $email,
                        'password' => Str::random(64),
                        'activated_at' => null,
                        'is_active' => true,
                        'created_by' => $actor->getKey(),
                    ]);
                } else {
                    $emailChanged = $account->email !== $email;

                    $account->forceFill([
                        'email' => $email,
                        'is_active' => true,
                    ])->save();

                    if ($emailChanged) {
                        $this->audit->tenantUser(
                            'portal_account_email_updated',
                            $actor,
                            $accountType.'_portal_account',
                            $account
                        );
                    }
                }

                if ($created) {
                    $this->audit->tenantUser(
                        'portal_account_created',
                        $actor,
                        $accountType.'_portal_account',
                        $account
                    );
                } elseif ($wasInactive) {
                    $this->audit->tenantUser(
                        'portal_account_enabled',
                        $actor,
                        $accountType.'_portal_account',
                        $account
                    );
                }

                $replaced = PortalInvitation::query()
                    ->where('account_type', $accountType)
                    ->where('account_id', $account->getKey())
                    ->whereNull('used_at')
                    ->whereNull('revoked_at')
                    ->update([
                        'revoked_at' => now(),
                        'updated_at' => now(),
                    ]);

                do {
                    $plainTextToken = Str::random(80);
                    $tokenHash = hash(
                        'sha256',
                        $plainTextToken
                    );
                } while (
                    PortalInvitation::query()
                        ->where('token_hash', $tokenHash)
                        ->exists()
                );

                $ttlMinutes = max(
                    15,
                    (int) config(
                        'portal.invitation_ttl_minutes',
                        1440
                    )
                );

                $invitation = PortalInvitation::query()
                    ->create([
                        'account_type' => $accountType,
                        'account_id' => $account->getKey(),
                        'token_hash' => $tokenHash,
                        'expires_at' => now()->addMinutes(
                            $ttlMinutes
                        ),
                        'created_by' => $actor->getKey(),
                    ]);

                $this->audit->tenantUser(
                    $replaced > 0
                        ? 'portal_invitation_replaced'
                        : 'portal_invitation_created',
                    $actor,
                    $accountType.'_portal_account',
                    $account,
                    [
                        'invitation_uuid' => $invitation->uuid,
                        'replaced_count' => $replaced,
                    ]
                );

                return [
                    'account' => $account
                        ->load('latestInvitation'),
                    'invitation' => $invitation,
                    'plain_text_token' => $plainTextToken,
                ];
            },
            3
        );

        $account = $issued['account'];
        $invitation = $issued['invitation'];

        if ($accountType === PortalInvitation::ACCOUNT_PATIENT) {
            PatientPortalInvitationCreated::dispatch(
                $tenant->id,
                $invitation->uuid,
                $account->uuid,
                $account->email
            );
        } else {
            ClinicianPortalInvitationCreated::dispatch(
                $tenant->id,
                $invitation->uuid,
                $account->uuid,
                $account->email
            );
        }

        return [
            'account' => $account,
            'invitation' => $invitation,
            'activation' => [
                'token' => $issued['plain_text_token'],
                'tenant_domain' => $tenant->domain,
                'path' => (string) config(
                    'portal.activation_path',
                    '/portal/activate'
                ),
                'expires_at' => $invitation->expires_at
                    ?->toIso8601String(),
            ],
        ];
    }

    private function usableInvitation(
        string $plainTextToken
    ): PortalInvitation {
        $invitation = PortalInvitation::query()
            ->where(
                'token_hash',
                hash('sha256', $plainTextToken)
            )
            ->first();

        if (! $this->isUsable($invitation)) {
            $this->invalidInvitation();
        }

        return $invitation;
    }

    private function isUsable(
        ?PortalInvitation $invitation
    ): bool {
        return $invitation !== null
            && $invitation->used_at === null
            && $invitation->revoked_at === null
            && $invitation->expires_at !== null
            && $invitation->expires_at->isFuture();
    }

    private function resolveAccount(
        PortalInvitation $invitation,
        bool $lock = false
    ): PatientPortalAccount|ClinicianPortalAccount {
        $query = $this->accountQuery(
            $invitation->account_type
        );

        if ($lock) {
            $query->lockForUpdate();
        }

        $account = $query->find(
            $invitation->account_id
        );

        if (! $account) {
            $this->invalidInvitation();
        }

        $account->load(
            $account instanceof PatientPortalAccount
                ? 'patient'
                : 'referringClinician'
        );

        return $account;
    }

    private function assertAccountMayActivate(
        PatientPortalAccount|ClinicianPortalAccount $account
    ): void {
        if (! $account->is_active || $account->activated_at !== null) {
            $this->invalidInvitation();
        }

        $this->assertLinkedSubjectActive($account);
    }

    private function assertLinkedSubjectActive(
        PatientPortalAccount|ClinicianPortalAccount $account
    ): void {
        $subject = $account instanceof PatientPortalAccount
            ? $account->patient
            : $account->referringClinician;

        if ($subject === null || ! $subject->is_active) {
            $this->invalidInvitation();
        }
    }

    private function displayName(
        PatientPortalAccount|ClinicianPortalAccount $account
    ): string {
        return $account instanceof PatientPortalAccount
            ? (string) $account->patient?->full_name
            : (string) $account->referringClinician?->name;
    }

    private function accountQuery(
        string $accountType
    ) {
        return match ($accountType) {
            PortalInvitation::ACCOUNT_PATIENT => (
                PatientPortalAccount::query()
            ),
            PortalInvitation::ACCOUNT_CLINICIAN => (
                ClinicianPortalAccount::query()
            ),
            default => throw new LogicException(
                'Unsupported portal account type.'
            ),
        };
    }

    private function invalidInvitation(): never
    {
        throw ValidationException::withMessages([
            'token' => [
                'The invitation is invalid or no longer available.',
            ],
        ]);
    }

    private function inactiveSubject(): never
    {
        throw ValidationException::withMessages([
            'subject' => [
                'Portal access cannot be enabled for an inactive record.',
            ],
        ]);
    }
}
