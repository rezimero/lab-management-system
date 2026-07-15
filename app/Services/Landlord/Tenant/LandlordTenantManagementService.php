<?php

namespace App\Services\Landlord\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Landlord\TenantContextRunner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class LandlordTenantManagementService
{
    public function __construct(
        protected TenantContextRunner $contextRunner,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(
        Tenant $tenant,
        array $data
    ): Tenant {
        $tenant->update($data);

        return $tenant->refresh();
    }

    public function setActive(
        Tenant $tenant,
        bool $isActive
    ): Tenant {
        $tenant->update([
            'is_active' => $isActive,
        ]);

        return $tenant->refresh();
    }

    public function delete(Tenant $tenant): void
    {
        $this->assertSafeDatabaseName(
            $tenant->database
        );

        DB::connection('landlord')->statement(
            sprintf(
                'DROP DATABASE IF EXISTS `%s`',
                $tenant->database
            )
        );

        $tenant->delete();
    }

    /**
     * Reset a tenant user's password.
     *
     * @return array{id: int, name: string, email: string}|null
     */
    public function resetAdminPassword(
        Tenant $tenant,
        string $email,
        string $password
    ): ?array {
        return $this->contextRunner->run(
            $tenant,
            static function () use (
                $email,
                $password
            ): ?array {
                $user = TenantUser::query()
                    ->where('email', $email)
                    ->first();

                if ($user === null) {
                    return null;
                }

                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                return [
                    'id' => (int) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ];
            }
        );
    }

    private function assertSafeDatabaseName(
        string $database
    ): void {
        if (
            ! preg_match(
                '/^[a-zA-Z0-9_-]+$/',
                $database
            )
        ) {
            throw new RuntimeException(
                'Unsafe tenant database name detected.'
            );
        }
    }
}