<?php

namespace App\Services\Tenant\Portal;

use App\Models\ClinicianPortalAccount;
use App\Models\PatientPortalAccount;
use App\Models\PortalAccessToken;
use App\Models\PortalInvitation;
use App\Models\PortalPasswordReset;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\Notification\NotificationPublisher;
use App\Services\Tenant\Notification\NotificationRecipient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;

class PortalPasswordResetService
{
    public function __construct(
        private readonly NotificationPublisher $publisher,
        private readonly PortalAuditLogger $audit
    ) {}

    public function requestByAdministrator(
        string $accountType,
        string $accountUuid,
        TenantUser $actor
    ): void {
        $account = $this->accountQuery($accountType)
            ->where('uuid', $accountUuid)
            ->firstOrFail();

        $this->assertAccountMayReset($account);
        $this->issue($accountType, $account, 'tenant_user', $actor);
    }

    public function requestSelfService(
        string $accountType,
        string $email
    ): void {
        if (! in_array(
            $accountType,
            [
                PortalInvitation::ACCOUNT_PATIENT,
                PortalInvitation::ACCOUNT_CLINICIAN,
            ],
            true
        )) {
            return;
        }

        $account = $this->accountQuery($accountType)
            ->where('email', strtolower(trim($email)))
            ->first();

        if (
            ! $account instanceof PatientPortalAccount
            && ! $account instanceof ClinicianPortalAccount
        ) {
            return;
        }

        if (! $this->accountMayReset($account)) {
            return;
        }

        $this->issue($accountType, $account, 'self_service', null);
    }

    /**
     * @return array<string, mixed>
     */
    public function inspect(string $plainTextToken): array
    {
        $reset = $this->usableReset($plainTextToken);
        $account = $this->resolveAccount($reset);
        $this->assertAccountMayReset($account);

        return [
            'account_type' => $reset->account_type,
            'email_hint' => $this->emailHint($account->email),
            'expires_at' => $reset->expires_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function complete(
        string $plainTextToken,
        string $password
    ): array {
        return DB::connection('tenant')->transaction(
            function () use (
                $plainTextToken,
                $password
            ): array {
                $reset = PortalPasswordReset::query()
                    ->where(
                        'token_hash',
                        hash('sha256', $plainTextToken)
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $this->isUsable($reset)) {
                    $this->invalidReset();
                }

                $account = $this->resolveAccount($reset, true);
                $this->assertAccountMayReset($account);
                $completedAt = now();

                $account->forceFill([
                    'password' => $password,
                ])->save();

                PortalAccessToken::query()
                    ->where('account_type', $reset->account_type)
                    ->where('account_id', $account->getKey())
                    ->whereNull('revoked_at')
                    ->update([
                        'revoked_at' => $completedAt,
                        'updated_at' => $completedAt,
                    ]);

                $reset->forceFill([
                    'used_at' => $completedAt,
                ])->save();

                $this->audit->system(
                    'portal_password_reset_completed',
                    $reset->account_type.'_portal_account',
                    $account,
                    [
                        'password_reset_uuid' => $reset->uuid,
                        'sessions_revoked' => true,
                    ]
                );

                return [
                    'message' => 'Password reset successfully.',
                    'account_type' => $reset->account_type,
                ];
            },
            3
        );
    }

    private function issue(
        string $accountType,
        PatientPortalAccount|ClinicianPortalAccount $account,
        string $requestedByType,
        ?TenantUser $actor
    ): void {
        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            throw new LogicException('No active tenant is available.');
        }

        DB::connection('tenant')->transaction(
            function () use (
                $accountType,
                $account,
                $requestedByType,
                $actor,
                $tenant
            ): void {
                $locked = $this->accountQuery($accountType)
                    ->whereKey($account->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertAccountMayReset($locked);

                PortalPasswordReset::query()
                    ->where('account_type', $accountType)
                    ->where('account_id', $locked->getKey())
                    ->whereNull('used_at')
                    ->whereNull('revoked_at')
                    ->update([
                        'revoked_at' => now(),
                        'updated_at' => now(),
                    ]);

                do {
                    $plainTextToken = Str::random(80);
                    $tokenHash = hash('sha256', $plainTextToken);
                } while (
                    PortalPasswordReset::query()
                        ->where('token_hash', $tokenHash)
                        ->exists()
                );

                $reset = PortalPasswordReset::query()->create([
                    'account_type' => $accountType,
                    'account_id' => $locked->getKey(),
                    'token_hash' => $tokenHash,
                    'requested_by_type' => $requestedByType,
                    'requested_by_id' => $actor?->getKey(),
                    'expires_at' => now()->addMinutes(
                        (int) config(
                            'rezhel_notifications.portal_password_reset_ttl_minutes',
                            60
                        )
                    ),
                ]);

                if ($actor instanceof TenantUser) {
                    $this->audit->tenantUser(
                        'portal_password_reset_requested',
                        $actor,
                        $accountType.'_portal_account',
                        $locked,
                        [
                            'password_reset_uuid' => $reset->uuid,
                        ]
                    );
                } else {
                    $this->audit->system(
                        'portal_password_reset_requested',
                        $accountType.'_portal_account',
                        $locked,
                        [
                            'password_reset_uuid' => $reset->uuid,
                            'request_source' => 'self_service',
                        ]
                    );
                }

                $url = $this->resetUrl(
                    $tenant->domain,
                    $accountType,
                    $plainTextToken
                );
                $recipient = $locked instanceof PatientPortalAccount
                    ? NotificationRecipient::patientPortal($locked)
                    : NotificationRecipient::clinicianPortal($locked);

                $this->publisher->publish(
                    recipient: $recipient,
                    idempotencyKey: 'portal-password-reset:'.$reset->uuid,
                    category: 'account_security',
                    eventKey: 'portal_password_reset_requested',
                    title: 'Reset your portal password',
                    body: (
                        'A secure one-time password reset link has been requested.'
                    ),
                    inApp: false,
                    mandatory: true,
                    email: [
                        'subject' => 'Reset your portal password',
                        'title' => 'Reset your portal password',
                        'body' => (
                            'Use this one-time link to establish a new portal password. '
                            .'The link expires automatically.'
                        ),
                        'action_label' => 'Reset portal password',
                        'action_url' => $url,
                        'footer' => (
                            'If you did not request this, ignore the message and '
                            .'contact the laboratory using a trusted phone number.'
                        ),
                    ],
                );
            },
            3
        );
    }

    private function resetUrl(
        string $tenantDomain,
        string $accountType,
        string $token
    ): string {
        $base = (string) config(
            'rezhel_notifications.portal_frontend_url'
        );
        $path = '/'.ltrim(
            (string) config(
                'rezhel_notifications.portal_password_reset_path',
                '/portal/reset-password'
            ),
            '/'
        );

        return $base.$path
            .'?tenant='.rawurlencode($tenantDomain)
            .'&type='.rawurlencode($accountType)
            .'#token='.rawurlencode($token);
    }

    private function usableReset(
        string $plainTextToken
    ): PortalPasswordReset {
        $reset = PortalPasswordReset::query()
            ->where(
                'token_hash',
                hash('sha256', $plainTextToken)
            )
            ->first();

        if (! $this->isUsable($reset)) {
            $this->invalidReset();
        }

        return $reset;
    }

    private function isUsable(
        ?PortalPasswordReset $reset
    ): bool {
        return $reset !== null
            && $reset->used_at === null
            && $reset->revoked_at === null
            && $reset->expires_at !== null
            && $reset->expires_at->isFuture();
    }

    private function resolveAccount(
        PortalPasswordReset $reset,
        bool $lock = false
    ): PatientPortalAccount|ClinicianPortalAccount {
        $query = $this->accountQuery($reset->account_type);

        if ($lock) {
            $query->lockForUpdate();
        }

        $account = $query->find($reset->account_id);

        if (
            ! $account instanceof PatientPortalAccount
            && ! $account instanceof ClinicianPortalAccount
        ) {
            $this->invalidReset();
        }

        $account->load(
            $account instanceof PatientPortalAccount
                ? 'patient'
                : 'referringClinician'
        );

        return $account;
    }

    private function assertAccountMayReset(
        PatientPortalAccount|ClinicianPortalAccount $account
    ): void {
        if ($this->accountMayReset($account)) {
            return;
        }

        throw ValidationException::withMessages([
            'account' => [
                'This portal account is not eligible for password reset.',
            ],
        ]);
    }

    private function accountMayReset(
        PatientPortalAccount|ClinicianPortalAccount $account
    ): bool {
        if (! $account->is_active || $account->activated_at === null) {
            return false;
        }

        if ($account instanceof PatientPortalAccount) {
            $account->loadMissing('patient');

            return $account->patient !== null
                && (bool) $account->patient->is_active;
        }

        $account->loadMissing('referringClinician');

        return $account->referringClinician !== null
            && (bool) $account->referringClinician->is_active;
    }

    /**
     * @return Builder<PatientPortalAccount|ClinicianPortalAccount>
     */
    private function accountQuery(string $accountType): Builder
    {
        return match ($accountType) {
            PortalInvitation::ACCOUNT_PATIENT => (
                PatientPortalAccount::query()
            ),
            PortalInvitation::ACCOUNT_CLINICIAN => (
                ClinicianPortalAccount::query()
            ),
            default => throw ValidationException::withMessages([
                'account_type' => ['Invalid portal account type.'],
            ]),
        };
    }

    private function emailHint(string $email): string
    {
        [$local, $domain] = array_pad(
            explode('@', $email, 2),
            2,
            ''
        );
        $visible = mb_substr($local, 0, 1);

        return $visible
            .str_repeat('*', max(2, mb_strlen($local) - 1))
            .'@'.$domain;
    }

    private function invalidReset(): never
    {
        throw ValidationException::withMessages([
            'token' => [
                'This password reset link is invalid or no longer available.',
            ],
        ]);
    }
}
