<?php

namespace App\Services\Tenant\Portal;

use App\Models\ClinicianPortalAccount;
use App\Models\PatientPortalAccount;
use App\Models\PortalAccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PortalAuthenticationService
{
    public function __construct(
        protected PortalAccessTokenService $tokens,
        protected PortalAuditLogger $audit,
    ) {}

    /**
     * @param  array{email: string, password: string}  $credentials
     * @return array<string, mixed>
     */
    public function loginPatient(
        array $credentials
    ): array {
        $account = PatientPortalAccount::query()
            ->with('patient')
            ->where(
                'email',
                $this->normalizeEmail(
                    $credentials['email']
                )
            )
            ->first();

        if (
            ! $account
            || ! Hash::check(
                $credentials['password'],
                $account->password
            )
            || ! $this->patientAccountCanAuthenticate(
                $account
            )
        ) {
            $this->invalidCredentials();
        }

        $account->forceFill([
            'last_login_at' => now(),
        ])->save();

        $issued = $this->tokens->issue(
            PortalAccessToken::ACCOUNT_PATIENT,
            $account
        );

        $this->audit->portalAccount(
            'portal_login_succeeded',
            $account
        );

        return array_merge(
            [
                'message' => 'Login successful.',
                'token' => $issued['plain_text_token'],
                'expires_at' => $issued['token']
                    ->expires_at
                    ?->toIso8601String(),
            ],
            $this->patientContext($account)
        );
    }

    /**
     * @param  array{email: string, password: string}  $credentials
     * @return array<string, mixed>
     */
    public function loginClinician(
        array $credentials
    ): array {
        $account = ClinicianPortalAccount::query()
            ->with('referringClinician')
            ->where(
                'email',
                $this->normalizeEmail(
                    $credentials['email']
                )
            )
            ->first();

        if (
            ! $account
            || ! Hash::check(
                $credentials['password'],
                $account->password
            )
            || ! $this->clinicianAccountCanAuthenticate(
                $account
            )
        ) {
            $this->invalidCredentials();
        }

        $account->forceFill([
            'last_login_at' => now(),
        ])->save();

        $issued = $this->tokens->issue(
            PortalAccessToken::ACCOUNT_CLINICIAN,
            $account
        );

        $this->audit->portalAccount(
            'portal_login_succeeded',
            $account
        );

        return array_merge(
            [
                'message' => 'Login successful.',
                'token' => $issued['plain_text_token'],
                'expires_at' => $issued['token']
                    ->expires_at
                    ?->toIso8601String(),
            ],
            $this->clinicianContext($account)
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function patientContext(
        PatientPortalAccount $account
    ): array {
        $account->loadMissing('patient');

        return [
            'portal' => PortalAccessToken::ACCOUNT_PATIENT,
            'account' => [
                'uuid' => $account->uuid,
                'email' => $account->email,
                'is_active' => (bool) $account->is_active,
                'activated_at' => $account->activated_at
                    ?->toIso8601String(),
                'last_login_at' => $account->last_login_at
                    ?->toIso8601String(),
            ],
            'patient' => [
                'medical_record_number' => (
                    $account->patient
                        ?->medical_record_number
                ),
                'first_name' => (
                    $account->patient
                        ?->first_name
                ),
                'last_name' => (
                    $account->patient
                        ?->last_name
                ),
                'full_name' => (
                    $account->patient
                        ?->full_name
                ),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function clinicianContext(
        ClinicianPortalAccount $account
    ): array {
        $account->loadMissing('referringClinician');

        return [
            'portal' => PortalAccessToken::ACCOUNT_CLINICIAN,
            'account' => [
                'uuid' => $account->uuid,
                'email' => $account->email,
                'is_active' => (bool) $account->is_active,
                'activated_at' => $account->activated_at
                    ?->toIso8601String(),
                'last_login_at' => $account->last_login_at
                    ?->toIso8601String(),
            ],
            'clinician' => [
                'uuid' => (
                    $account->referringClinician
                        ?->uuid
                ),
                'name' => (
                    $account->referringClinician
                        ?->name
                ),
                'email' => (
                    $account->referringClinician
                        ?->email
                ),
                'phone' => (
                    $account->referringClinician
                        ?->phone
                ),
                'organisation' => (
                    $account->referringClinician
                        ?->organisation
                ),
                'registration_number' => (
                    $account->referringClinician
                        ?->registration_number
                ),
                'specialty' => (
                    $account->referringClinician
                        ?->specialty
                ),
            ],
        ];
    }

    public function logout(Request $request): void
    {
        $token = $request->attributes->get(
            'portal_access_token'
        );

        if ($token instanceof PortalAccessToken) {
            $this->tokens->revoke($token);

            $account = $request->attributes->get(
                'portal_account'
            );

            if (
                $account instanceof PatientPortalAccount
                || $account instanceof ClinicianPortalAccount
            ) {
                $this->audit->portalAccount(
                    'portal_logout_token_revoked',
                    $account,
                    [
                        'token_uuid' => $token->uuid,
                    ]
                );
            }
        }
    }

    public function patientAccountCanAuthenticate(
        PatientPortalAccount $account
    ): bool {
        return (bool) $account->is_active
            && $account->activated_at !== null
            && $account->patient !== null
            && (bool) $account->patient->is_active;
    }

    public function clinicianAccountCanAuthenticate(
        ClinicianPortalAccount $account
    ): bool {
        return (bool) $account->is_active
            && $account->activated_at !== null
            && $account->referringClinician !== null
            && (bool) $account
                ->referringClinician
                ->is_active;
    }

    private function normalizeEmail(
        string $email
    ): string {
        return strtolower(trim($email));
    }

    private function invalidCredentials(): never
    {
        throw ValidationException::withMessages([
            'email' => [
                'Invalid credentials.',
            ],
        ]);
    }
}
