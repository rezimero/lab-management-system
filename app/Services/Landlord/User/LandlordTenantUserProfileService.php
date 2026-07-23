<?php

namespace App\Services\Landlord\User;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\User\TenantAdministratorGuard;
use App\Services\Tenant\User\TenantUserSessionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LandlordTenantUserProfileService
{
    private const SESSION_SENSITIVE_FIELDS = [
        'email',
        'password',
        'role',
        'is_active',
    ];

    public function __construct(
        protected TenantAdministratorGuard $administratorGuard,
        protected TenantUserSessionService $sessionService,
        protected LandlordTenantUserAuditLogger $auditLogger
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(
        Tenant $tenant,
        array $data,
        mixed $actorId
    ): TenantUser {
        $user = DB::connection('tenant')->transaction(
            function () use ($data): TenantUser {
                $user = new TenantUser();

                $user->forceFill([
                    'name' => trim($data['name']),
                    'email' => mb_strtolower(
                        trim($data['email'])
                    ),
                    'password' => Hash::make(
                        $data['password']
                    ),
                    'role' => $data['role'],
                    'is_active' =>
                        $data['is_active'] ?? true,
                    'email_verified_at' =>
                        ($data['email_verified'] ?? false)
                            ? now()
                            : null,
                ]);

                $user->save();

                return $user->fresh();
            }
        );

        $this->auditLogger->log(
            event: 'tenant_user_created',
            actorId: $actorId,
            tenant: $tenant,
            user: $user,
            changedFields: [
                'name',
                'email',
                'role',
                'is_active',
                'email_verified_at',
                'password',
            ]
        );

        return $user;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array{
     *     user: TenantUser,
     *     changed_fields: array<int, string>,
     *     sessions_revoked: bool
     * }
     */
    public function update(
        Tenant $tenant,
        TenantUser $user,
        array $data,
        mixed $actorId
    ): array {
        $nextRole = $data['role'] ?? $user->role;
        $nextActive = $data['is_active']
            ?? $user->is_active;

        $this->administratorGuard
            ->ensureAdministratorContinuity(
                target: $user,
                nextRole: $nextRole,
                nextActive: (bool) $nextActive
            );

        $changedFields = [];

        $updatedUser = DB::connection('tenant')
            ->transaction(
                function () use (
                    $user,
                    $data,
                    &$changedFields
                ): TenantUser {
                    $this->applyProfileFields(
                        $user,
                        $data,
                        $changedFields
                    );

                    $this->applyEmailVerification(
                        $user,
                        $data,
                        $changedFields
                    );

                    $this->applyPassword(
                        $user,
                        $data,
                        $changedFields
                    );

                    $user->save();

                    return $user->fresh();
                }
            );

        $changedFields = array_values(
            array_unique($changedFields)
        );

        $sessionsRevoked = $this
            ->requiresTokenRevocation($changedFields);

        if ($sessionsRevoked) {
            $this->sessionService->revokeAll(
                $tenant,
                $updatedUser
            );
        }

        $this->auditLogger->log(
            event: 'tenant_user_updated',
            actorId: $actorId,
            tenant: $tenant,
            user: $updatedUser,
            changedFields: $changedFields
        );

        return [
            'user' => $updatedUser,
            'changed_fields' => $changedFields,
            'sessions_revoked' => $sessionsRevoked,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $changedFields
     */
    private function applyProfileFields(
        TenantUser $user,
        array $data,
        array &$changedFields
    ): void {
        foreach (
            ['name', 'email', 'role', 'is_active']
            as $field
        ) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];

            if ($field === 'name') {
                $value = trim((string) $value);
            }

            if ($field === 'email') {
                $value = mb_strtolower(
                    trim((string) $value)
                );
            }

            if ($user->getAttribute($field) !== $value) {
                $changedFields[] = $field;
            }

            $user->setAttribute($field, $value);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $changedFields
     */
    private function applyEmailVerification(
        TenantUser $user,
        array $data,
        array &$changedFields
    ): void {
        if (! array_key_exists('email_verified', $data)) {
            return;
        }

        $verifiedAt = $data['email_verified']
            ? ($user->email_verified_at ?? now())
            : null;

        if (
            $user->email_verified_at?->toDateTimeString()
            !== $verifiedAt?->toDateTimeString()
        ) {
            $changedFields[] = 'email_verified_at';
        }

        $user->email_verified_at = $verifiedAt;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $changedFields
     */
    private function applyPassword(
        TenantUser $user,
        array $data,
        array &$changedFields
    ): void {
        if (! filled($data['password'] ?? null)) {
            return;
        }

        $user->password = Hash::make(
            $data['password']
        );

        $changedFields[] = 'password';
    }

    /**
     * @param array<int, string> $changedFields
     */
    private function requiresTokenRevocation(
        array $changedFields
    ): bool {
        return array_intersect(
            $changedFields,
            self::SESSION_SENSITIVE_FIELDS
        ) !== [];
    }
}
