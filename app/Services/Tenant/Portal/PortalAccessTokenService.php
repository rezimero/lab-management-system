<?php

namespace App\Services\Tenant\Portal;

use App\Models\ClinicianPortalAccount;
use App\Models\PatientPortalAccount;
use App\Models\PortalAccessToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;

class PortalAccessTokenService
{
    /**
     * @return array{
     *     token: PortalAccessToken,
     *     plain_text_token: string
     * }
     */
    public function issue(
        string $accountType,
        Model $account,
        string $name = 'portal_session'
    ): array {
        $this->assertSupportedAccount(
            $accountType,
            $account
        );

        do {
            $plainTextToken = Str::random(80);
            $tokenHash = hash(
                'sha256',
                $plainTextToken
            );
        } while (
            PortalAccessToken::query()
                ->where('token_hash', $tokenHash)
                ->exists()
        );

        $ttlMinutes = max(
            5,
            (int) config(
                'portal.access_token_ttl_minutes',
                720
            )
        );

        $token = PortalAccessToken::query()->create([
            'account_type' => $accountType,
            'account_id' => (int) $account->getKey(),
            'token_hash' => $tokenHash,
            'name' => $name,
            'abilities' => [
                'portal:'.$accountType,
            ],
            'expires_at' => now()->addMinutes(
                $ttlMinutes
            ),
        ]);

        return [
            'token' => $token,
            'plain_text_token' => $plainTextToken,
        ];
    }

    public function findUsable(
        string $plainTextToken
    ): ?PortalAccessToken {
        if ($plainTextToken === '') {
            return null;
        }

        return PortalAccessToken::query()
            ->where(
                'token_hash',
                hash('sha256', $plainTextToken)
            )
            ->whereNull('revoked_at')
            ->where(
                'expires_at',
                '>',
                now()
            )
            ->first();
    }

    public function resolveAccount(
        PortalAccessToken $token
    ): PatientPortalAccount|ClinicianPortalAccount|null {
        if (
            $token->account_type
            === PortalAccessToken::ACCOUNT_PATIENT
        ) {
            return PatientPortalAccount::query()
                ->with('patient')
                ->find($token->account_id);
        }

        if (
            $token->account_type
            === PortalAccessToken::ACCOUNT_CLINICIAN
        ) {
            return ClinicianPortalAccount::query()
                ->with('referringClinician')
                ->find($token->account_id);
        }

        return null;
    }

    public function revoke(
        PortalAccessToken $token
    ): void {
        if ($token->revoked_at !== null) {
            return;
        }

        $token->forceFill([
            'revoked_at' => now(),
        ])->save();
    }

    public function touchLastUsed(
        PortalAccessToken $token
    ): void {
        $cutoff = now()->subMinutes(5);

        if (
            $token->last_used_at !== null
            && $token->last_used_at->greaterThan(
                $cutoff
            )
        ) {
            return;
        }

        $touchedAt = now();

        $updated = PortalAccessToken::query()
            ->whereKey($token->getKey())
            ->where(
                function ($query) use ($cutoff): void {
                    $query->whereNull('last_used_at')
                        ->orWhere(
                            'last_used_at',
                            '<=',
                            $cutoff
                        );
                }
            )
            ->update([
                'last_used_at' => $touchedAt,
                'updated_at' => $touchedAt,
            ]);

        if ($updated === 1) {
            $token->last_used_at = $touchedAt;
        }
    }

    private function assertSupportedAccount(
        string $accountType,
        Model $account
    ): void {
        $valid = match ($accountType) {
            PortalAccessToken::ACCOUNT_PATIENT => $account instanceof PatientPortalAccount,
            PortalAccessToken::ACCOUNT_CLINICIAN => $account instanceof ClinicianPortalAccount,
            default => false,
        };

        if (! $valid) {
            throw new LogicException(
                'Unsupported portal access-token account.'
            );
        }
    }
}
