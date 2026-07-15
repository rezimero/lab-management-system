<?php

namespace App\Services\Tenant\User;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantUserService
{
    /**
     * Create a tenant user.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): TenantUser
    {
        return DB::connection('tenant')->transaction(
            function () use ($data): TenantUser {
                return TenantUser::query()->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'role' => $data['role'],
                    'is_active' => $data['is_active'] ?? true,
                ]);
            }
        );
    }

    /**
     * Update a tenant user.
     *
     * @param array<string, mixed> $data
     */
    public function update(
        TenantUser $user,
        array $data
    ): TenantUser {
        return DB::connection('tenant')->transaction(
            function () use ($user, $data): TenantUser {
                $updateData = Arr::only($data, [
                    'name',
                    'email',
                    'role',
                    'is_active',
                ]);

                if (array_key_exists('password', $data)) {
                    $updateData['password'] = Hash::make(
                        $data['password']
                    );
                }

                $user->update($updateData);

                return $user->fresh();
            }
        );
    }

    public function delete(TenantUser $user): void
    {
        DB::connection('tenant')->transaction(
            static function () use ($user): void {
                $user->delete();
            }
        );
    }

    public function assignRole(
        TenantUser $user,
        string $role
    ): TenantUser {
        return DB::connection('tenant')->transaction(
            static function () use ($user, $role): TenantUser {
                $user->update([
                    'role' => $role,
                ]);

                return $user->fresh();
            }
        );
    }
}