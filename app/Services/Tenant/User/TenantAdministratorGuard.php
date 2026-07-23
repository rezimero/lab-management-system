<?php

namespace App\Services\Tenant\User;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Validation\ValidationException;

class TenantAdministratorGuard
{
    public function ensureCanManage(
        TenantUser $actor
    ): void {
        if (
            $actor->role !== 'admin'
            || ! (bool) $actor->is_active
        ) {
            abort(
                403,
                'Only an active tenant administrator may manage user accounts.'
            );
        }
    }

    public function ensureCanView(
        TenantUser $actor,
        TenantUser $target
    ): void {
        if (
            (int) $actor->getKey()
            === (int) $target->getKey()
        ) {
            return;
        }

        $this->ensureCanManage($actor);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function ensureSelfUpdateIsSafe(
        TenantUser $user,
        array $data
    ): void {
        if (
            array_key_exists('role', $data)
            && $data['role'] !== $user->role
        ) {
            throw ValidationException::withMessages([
                'role' => [
                    'You cannot change your own role.',
                ],
            ]);
        }

        if (
            array_key_exists('is_active', $data)
            && ! (bool) $data['is_active']
        ) {
            throw ValidationException::withMessages([
                'is_active' => [
                    'You cannot suspend your own account.',
                ],
            ]);
        }
    }

    public function ensureNotSelf(
        TenantUser $actor,
        TenantUser $target,
        string $message
    ): void {
        if (
            (int) $actor->getKey()
            !== (int) $target->getKey()
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'user' => [$message],
        ]);
    }

    public function ensureAdministratorContinuity(
        TenantUser $target,
        string $nextRole,
        bool $nextActive
    ): void {
        $currentlyActiveAdministrator =
            $target->role === 'admin'
            && (bool) $target->is_active;

        $willRemainActiveAdministrator =
            $nextRole === 'admin'
            && $nextActive;

        if (
            ! $currentlyActiveAdministrator
            || $willRemainActiveAdministrator
        ) {
            return;
        }

        $otherActiveAdministrators =
            TenantUser::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->where(
                    'id',
                    '!=',
                    $target->getKey()
                )
                ->count();

        if ($otherActiveAdministrators > 0) {
            return;
        }

        throw ValidationException::withMessages([
            'user' => [
                'This account is the final active tenant administrator. Create or activate another administrator before demoting, suspending or deleting it.',
            ],
        ]);
    }
}