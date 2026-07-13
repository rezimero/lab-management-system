<?php

namespace App\Services\Authentication;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\Provisioning\TenantProvisioner;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class RegistrationService
{
    public function __construct(
        protected TenantProvisioner $tenantProvisioner,
        protected SessionService $sessionService,
        protected AuthenticationService $authenticationService,
    ) {
    }

    /**
     * Register a tenant and authenticate its first administrator.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function register(array $data): array
    {
        $result = $this->tenantProvisioner->provision(
            data: [
                'tenant_name' => trim(
                    $data['tenant_name']
                ),
                'tenant_domain' => strtolower(
                    trim($data['tenant_domain'])
                ),
                'admin_name' => trim($data['name']),
                'admin_email' => strtolower(
                    trim($data['email'])
                ),
                'admin_password' => $data['password'],
                'settings' => [],
            ],
            withinTenant: function (
                Tenant $tenant,
                TenantUser $administrator
            ): string {
                return $this->authenticationService
                    ->createToken(
                        $administrator,
                        'auth_token'
                    );
            },
            afterRestore: function (
                Tenant $tenant,
                TenantUser $administrator
            ): void {
                try {
                    $this->sessionService
                        ->startTenantSession(
                            $tenant,
                            $administrator
                        );
                } catch (Throwable $exception) {
                    $this->sessionService->clear();

                    throw $exception;
                }
            }
        );

        $tenant = $result->tenant;
        $administrator = $result->administrator;
        $token = $result->contextResult;

        Log::info(
            'Tenant registration completed successfully.',
            [
                'tenant_id' => $tenant->id,
                'tenant_domain' => $tenant->domain,
                'tenant_database' => $tenant->database,
                'administrator_id' => $administrator->id,
                'administrator_email' => (
                    $administrator->email
                ),
            ]
        );

        return [
            'message' => (
                'Tenant and administrator created successfully.'
            ),

            'user' => [
                'id' => $administrator->id,
                'name' => $administrator->name,
                'email' => $administrator->email,
                'role' => $administrator->role,
                'is_active' => (
                    (bool) $administrator->is_active
                ),
            ],

            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'database' => $tenant->database,
                'is_active' => (bool) $tenant->is_active,
                'trial_ends_at' => $tenant->trial_ends_at,
            ],

            'token' => $token,
            'guard' => 'tenant',
            'is_superadmin' => false,
        ];
    }

    /**
     * Validate public tenant-registration business rules.
     *
     * @param array<string, mixed> $data
     */
    public function validate(array $data): void
    {
        $domain = strtolower(
            trim($data['tenant_domain'])
        );

        if (
            ! preg_match(
                '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $domain
            )
        ) {
            throw ValidationException::withMessages([
                'tenant_domain' => [
                    'The tenant domain may contain '
                    .'lowercase letters, numbers and '
                    .'single hyphens only.',
                ],
            ]);
        }

        if (strlen($domain) > 63) {
            throw ValidationException::withMessages([
                'tenant_domain' => [
                    'The tenant domain may not be greater '
                    .'than 63 characters.',
                ],
            ]);
        }

        if (
            Tenant::query()
                ->where('domain', $domain)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'tenant_domain' => [
                    'The selected tenant domain already exists.',
                ],
            ]);
        }
    }
}