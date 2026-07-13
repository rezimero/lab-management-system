<?php

namespace Tests\Support\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Authentication\TenantSwitcher;
use Closure;
use Illuminate\Support\Str;
use Tests\Support\Authentication\TenantAuthenticationTestCase;

abstract class UserManagementTestCase extends TenantAuthenticationTestCase
{
    /**
     * Authenticate the administrator created during tenant registration.
     */
    protected function loginRegisteredTenant(
        array $registration
    ): string {
        $response = $this->postJson('/api/login', [
            'domain' => $registration['domain'],
            'email' => $registration['email'],
            'password' => $registration['password'],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('guard', 'tenant')
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonPath(
                'tenant.domain',
                $registration['domain']
            );

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $this->restoreLandlordContext();

        return $token;
    }

    /**
     * Create a user directly in a temporary tenant database.
     */
    protected function createTenantUser(
        Tenant $tenant,
        array $attributes = []
    ): TenantUser {
        return $this->runInTenant(
            $tenant,
            function () use ($attributes): TenantUser {
                $defaults = [
                    'name' => 'Tenant Test User',
                    'email' => sprintf(
                        'tenant-user-%s@example.test',
                        strtolower(Str::random(12))
                    ),
                    'password' => 'Password123!',
                    'role' => 'receptionist',
                    'is_active' => true,
                ];

                return TenantUser::query()->create(
                    array_merge($defaults, $attributes)
                );
            }
        );
    }

    /**
     * Find a user by email inside a tenant database.
     */
    protected function findTenantUserByEmail(
        Tenant $tenant,
        string $email
    ): ?TenantUser {
        return $this->runInTenant(
            $tenant,
            static fn (): ?TenantUser => TenantUser::query()
                ->where('email', $email)
                ->first()
        );
    }

    /**
     * Determine whether a tenant user exists.
     */
    protected function tenantUserExists(
        Tenant $tenant,
        array $attributes
    ): bool {
        return $this->runInTenant(
            $tenant,
            static fn (): bool => TenantUser::query()
                ->where($attributes)
                ->exists()
        );
    }

    /**
     * Execute an operation within a tenant and always restore landlord.
     */
    protected function runInTenant(
        Tenant $tenant,
        Closure $operation
    ): mixed {
        try {
            app(TenantSwitcher::class)->switch($tenant);

            return $operation();
        } finally {
            $this->restoreLandlordContext();
        }
    }
}