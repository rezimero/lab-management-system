<?php

namespace App\Services\Authentication;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\Branch\BranchBootstrapService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class TenantAuthenticationService
{
    public function __construct(
        protected TenantSwitcher $tenantSwitcher,
        protected SessionService $sessionService,
        protected AuthenticationService $authenticationService,
        protected BranchBootstrapService $branchBootstrap,
    ) {}

    /**
     * Authenticate a tenant user.
     */
    public function login(array $credentials): array
    {
        $tenant = $this->resolveTenant($credentials['domain']);

        $this->tenantSwitcher->switch($tenant);

        $user = $this->resolveUser(
            $credentials['email'],
            $credentials['password']
        );

        if (! $user->branches()->exists()) {
            $this->branchBootstrap->assignDefault(
                $user,
                $user
            );
        }

        // Start authenticated tenant session
        $this->sessionService->startTenantSession(
            $tenant,
            $user
        );

        return [
            'message' => 'Login successful',

            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ],

            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
            ],

            'token' => $this->authenticationService->createToken($user),

            'guard' => 'tenant',

            'is_superadmin' => false,
        ];
    }

    /**
     * Resolve and validate the tenant.
     */
    protected function resolveTenant(string $domain): Tenant
    {
        $tenant = Tenant::query()
            ->where('domain', $domain)
            ->first();

        if (! $tenant) {
            throw ValidationException::withMessages([
                'domain' => 'Invalid laboratory domain.',
            ]);
        }

        if (! $tenant->is_active) {
            throw ValidationException::withMessages([
                'domain' => 'This laboratory has been deactivated.',
            ]);
        }

        return $tenant;
    }

    /**
     * Resolve and validate the tenant user.
     */
    protected function resolveUser(
        string $email,
        string $password
    ): TenantUser {

        $user = TenantUser::query()
            ->where('email', $email)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Invalid credentials.',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated.',
            ]);
        }

        return $user;
    }
}
