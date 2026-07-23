<?php

namespace App\Services\Landlord\User;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\User\TenantAdministratorGuard;
use App\Services\Tenant\User\TenantUserSessionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LandlordTenantUserAccessService
{
    public function __construct(
        protected TenantAdministratorGuard $administratorGuard,
        protected TenantUserSessionService $sessionService,
        protected LandlordTenantUserAuditLogger $auditLogger
    ) {
    }

    public function delete(
        Tenant $tenant,
        TenantUser $user,
        mixed $actorId
    ): void {
        $this->administratorGuard
            ->ensureAdministratorContinuity(
                target: $user,
                nextRole: '__deleted__',
                nextActive: false
            );

        $userId = (int) $user->getKey();

        $this->sessionService->revokeAll(
            $tenant,
            $user
        );

        DB::connection('tenant')->transaction(
            static function () use ($user): void {
                $user->delete();
            }
        );

        $this->auditLogger->log(
            event: 'tenant_user_deleted',
            actorId: $actorId,
            tenant: $tenant,
            userId: $userId,
            changedFields: ['account_deleted']
        );
    }

    public function resetPassword(
        Tenant $tenant,
        TenantUser $user,
        string $password,
        mixed $actorId
    ): TenantUser {
        DB::connection('tenant')->transaction(
            function () use ($user, $password): void {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        $this->sessionService->revokeAll(
            $tenant,
            $user
        );

        $this->auditLogger->log(
            event: 'tenant_user_password_reset',
            actorId: $actorId,
            tenant: $tenant,
            user: $user,
            changedFields: [
                'password',
                'sessions_revoked',
            ]
        );

        return $user->fresh();
    }

    /**
     * @return array{
     *     user: TenantUser,
     *     sessions_revoked: bool
     * }
     */
    public function assignRole(
        Tenant $tenant,
        TenantUser $user,
        string $role,
        mixed $actorId
    ): array {
        $this->administratorGuard
            ->ensureAdministratorContinuity(
                target: $user,
                nextRole: $role,
                nextActive: (bool) $user->is_active
            );

        $roleChanged = $user->role !== $role;

        if ($roleChanged) {
            DB::connection('tenant')->transaction(
                function () use ($user, $role): void {
                    $user->role = $role;
                    $user->save();
                }
            );

            $this->sessionService->revokeAll(
                $tenant,
                $user
            );
        }

        $this->auditLogger->log(
            event: 'tenant_user_role_assigned',
            actorId: $actorId,
            tenant: $tenant,
            user: $user,
            changedFields: $roleChanged
                ? ['role']
                : []
        );

        return [
            'user' => $user->fresh(),
            'sessions_revoked' => $roleChanged,
        ];
    }
}
