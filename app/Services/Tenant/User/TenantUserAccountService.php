<?php

namespace App\Services\Tenant\User;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\Branch\BranchBootstrapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class TenantUserAccountService
{
    public function __construct(
        protected TenantUserService $users,
        protected TenantAdministratorGuard $guard,
        protected TenantUserSessionService $sessions,
        protected BranchBootstrapService $branchBootstrap,
    ) {}

    public function create(
        Tenant $tenant,
        TenantUser $actor,
        array $data
    ): TenantUser {
        $this->guard->ensureCanManage($actor);

        if ($data['role'] === 'superadmin') {
            abort(403, 'Unauthorized to create superadmin user');
        }

        unset(
            $data['password_confirmation'],
            $data['current_password']
        );

        $data = $this->normalise($data);
        $user = $this->users->create($data);

        $this->branchBootstrap->assignDefault(
            $user,
            $actor
        );

        $this->audit(
            'tenant_user_created',
            $actor,
            $tenant,
            (int) $user->getKey(),
            [
                'name',
                'email',
                'password',
                'role',
                'is_active',
            ]
        );

        return $user;
    }

    /**
     * @return array{
     *     user: TenantUser,
     *     self_service: bool,
     *     sessions_revoked: bool
     * }
     */
    public function update(
        Request $request,
        Tenant $tenant,
        TenantUser $actor,
        TenantUser $target,
        array $data
    ): array {
        if ((int) $actor->getKey() === (int) $target->getKey()) {
            return $this->updateOwnAccount(
                $request,
                $tenant,
                $target,
                $data
            );
        }

        $this->guard->ensureCanManage($actor);

        return $this->updateManagedAccount(
            $tenant,
            $actor,
            $target,
            $data
        );
    }

    public function delete(
        Tenant $tenant,
        TenantUser $actor,
        TenantUser $target
    ): void {
        $this->guard->ensureCanManage($actor);

        $this->guard->ensureNotSelf(
            $actor,
            $target,
            'Cannot delete your own account'
        );

        $this->guard->ensureAdministratorContinuity(
            $target,
            '__deleted__',
            false
        );

        $targetId = (int) $target->getKey();

        $this->sessions->revokeAll($tenant, $target);
        $this->users->delete($target);

        $this->audit(
            'tenant_user_deleted',
            $actor,
            $tenant,
            $targetId,
            [
                'account_deleted',
                'sessions_revoked',
            ]
        );
    }

    /**
     * @return array{
     *     user: TenantUser,
     *     sessions_revoked: bool
     * }
     */
    public function assignRole(
        Tenant $tenant,
        TenantUser $actor,
        TenantUser $target,
        string $role
    ): array {
        $this->guard->ensureCanManage($actor);

        $this->guard->ensureNotSelf(
            $actor,
            $target,
            'You cannot change your own role'
        );

        if ($role === 'superadmin') {
            abort(403, 'Unauthorized');
        }

        $this->guard->ensureAdministratorContinuity(
            $target,
            $role,
            (bool) $target->is_active
        );

        $roleChanged = $target->role !== $role;

        $updatedUser = $this->users->assignRole(
            $target,
            $role
        );

        if ($roleChanged) {
            $this->sessions->revokeAll(
                $tenant,
                $updatedUser
            );
        }

        $this->audit(
            'tenant_user_role_assigned',
            $actor,
            $tenant,
            (int) $updatedUser->getKey(),
            $roleChanged
                ? ['role', 'sessions_revoked']
                : []
        );

        return [
            'user' => $updatedUser,
            'sessions_revoked' => $roleChanged,
        ];
    }

    /**
     * @return array{
     *     user: TenantUser,
     *     self_service: bool,
     *     sessions_revoked: bool
     * }
     */
    private function updateOwnAccount(
        Request $request,
        Tenant $tenant,
        TenantUser $user,
        array $data
    ): array {
        $this->guard->ensureSelfUpdateIsSafe(
            $user,
            $data
        );

        $data = $this->normalise($data);

        $emailChanged =
            array_key_exists('email', $data)
            && $data['email'] !== $user->email;

        $passwordChanged =
            filled($data['password'] ?? null);

        if ($emailChanged || $passwordChanged) {
            $this->verifyCurrentPassword(
                $user,
                $data['current_password'] ?? null
            );
        }

        if ($passwordChanged) {
            $this->validateNewPassword(
                (string) $data['password'],
                $data['password_confirmation'] ?? null
            );
        }

        unset(
            $data['role'],
            $data['is_active'],
            $data['current_password'],
            $data['password_confirmation']
        );

        $changedFields = $this->changedFields(
            $user,
            $data
        );

        $updatedUser = $this->users->update(
            $user,
            $data
        );

        $sessionsRevoked =
            $emailChanged || $passwordChanged;

        if ($sessionsRevoked) {
            $this->sessions->revokeOthers(
                $tenant,
                $updatedUser,
                $this->sessions->currentTokenId(
                    $request
                )
            );
        }

        $this->audit(
            'tenant_user_self_updated',
            $updatedUser,
            $tenant,
            (int) $updatedUser->getKey(),
            array_merge(
                $changedFields,
                $sessionsRevoked
                    ? ['other_sessions_revoked']
                    : []
            )
        );

        return [
            'user' => $updatedUser,
            'self_service' => true,
            'sessions_revoked' => $sessionsRevoked,
        ];
    }

    /**
     * @return array{
     *     user: TenantUser,
     *     self_service: bool,
     *     sessions_revoked: bool
     * }
     */
    private function updateManagedAccount(
        Tenant $tenant,
        TenantUser $actor,
        TenantUser $target,
        array $data
    ): array {
        if (($data['role'] ?? null) === 'superadmin') {
            abort(403, 'Unauthorized');
        }

        $data = $this->normalise($data);

        unset(
            $data['current_password'],
            $data['password_confirmation']
        );

        $nextRole = $data['role'] ?? $target->role;
        $nextActive = $data['is_active'] ?? $target->is_active;

        $this->guard->ensureAdministratorContinuity(
            $target,
            $nextRole,
            (bool) $nextActive
        );

        $changedFields = $this->changedFields(
            $target,
            $data
        );

        $updatedUser = $this->users->update(
            $target,
            $data
        );

        $sessionsRevoked =
            $this->containsSensitiveChange(
                $changedFields
            );

        if ($sessionsRevoked) {
            $this->sessions->revokeAll(
                $tenant,
                $updatedUser
            );
        }

        $this->audit(
            'tenant_user_admin_updated',
            $actor,
            $tenant,
            (int) $updatedUser->getKey(),
            array_merge(
                $changedFields,
                $sessionsRevoked
                    ? ['sessions_revoked']
                    : []
            )
        );

        return [
            'user' => $updatedUser,
            'self_service' => false,
            'sessions_revoked' => $sessionsRevoked,
        ];
    }

    private function verifyCurrentPassword(
        TenantUser $user,
        mixed $currentPassword
    ): void {
        if (
            ! is_string($currentPassword)
            || $currentPassword === ''
            || ! Hash::check(
                $currentPassword,
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'current_password' => [
                    'The current password is incorrect.',
                ],
            ]);
        }
    }

    private function validateNewPassword(
        string $password,
        mixed $confirmation
    ): void {
        if (
            ! is_string($confirmation)
            || $password !== $confirmation
        ) {
            throw ValidationException::withMessages([
                'password_confirmation' => [
                    'The password confirmation does not match.',
                ],
            ]);
        }

        Validator::make(
            ['password' => $password],
            [
                'password' => [
                    'required',
                    'string',
                    Password::min(12)
                        ->letters()
                        ->mixedCase()
                        ->numbers()
                        ->symbols(),
                ],
            ]
        )->validate();
    }

    private function normalise(array $data): array
    {
        if (array_key_exists('name', $data)) {
            $data['name'] = trim(
                (string) $data['name']
            );
        }

        if (array_key_exists('email', $data)) {
            $data['email'] = mb_strtolower(
                trim((string) $data['email'])
            );
        }

        return $data;
    }

    /**
     * @return array<int, string>
     */
    private function changedFields(
        TenantUser $target,
        array $data
    ): array {
        $changedFields = [];

        foreach (
            ['name', 'email', 'role', 'is_active'] as $field
        ) {
            if (
                array_key_exists($field, $data)
                && $target->getAttribute($field)
                    !== $data[$field]
            ) {
                $changedFields[] = $field;
            }
        }

        if (filled($data['password'] ?? null)) {
            $changedFields[] = 'password';
        }

        return array_values(
            array_unique($changedFields)
        );
    }

    /**
     * @param  array<int, string>  $changedFields
     */
    private function containsSensitiveChange(
        array $changedFields
    ): bool {
        return array_intersect(
            $changedFields,
            [
                'email',
                'password',
                'role',
                'is_active',
            ]
        ) !== [];
    }

    /**
     * @param  array<int, string>  $changedFields
     */
    private function audit(
        string $event,
        TenantUser $actor,
        Tenant $tenant,
        int $targetId,
        array $changedFields
    ): void {
        Log::notice(
            'Rezhel tenant user-governance action.',
            [
                'event' => $event,
                'actor_id' => $actor->getKey(),
                'tenant_id' => $tenant->getKey(),
                'tenant_user_id' => $targetId,
                'changed_fields' => array_values(
                    array_unique($changedFields)
                ),
            ]
        );
    }
}
