<?php

namespace Tests\Feature\Landlord\Tenant;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Landlord\TenantManagementTestCase;

class LandlordTenantProvisioningTest extends TenantManagementTestCase
{
    public function test_superadmin_can_provision_a_tenant_and_its_admin(): void
    {
        $domain = $this->uniqueManagedDomain('store');
        $adminEmail = $domain.'@example.test';
        $adminPassword = 'Password123!';

        $this->trackManagedTenantDomain($domain);

        $headers = $this->superadminHeaders();

        $response = $this
            ->withHeaders($headers)
            ->postJson('/api/landlord/tenants', [
                'name' => 'Provisioned Landlord Test Laboratory',
                'domain' => $domain,
                'admin_name' => 'Provisioned Administrator',
                'admin_email' => $adminEmail,
                'admin_password' => $adminPassword,
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Tenant created successfully'
            )
            ->assertJsonPath(
                'tenant.name',
                'Provisioned Landlord Test Laboratory'
            )
            ->assertJsonPath(
                'tenant.domain',
                $domain
            )
            ->assertJsonPath(
                'tenant.is_active',
                true
            )
            ->assertJsonPath(
                'tenant.stats.users',
                1
            );

        $this->restoreLandlordContext();

        $tenant = Tenant::query()
            ->where('domain', $domain)
            ->firstOrFail();

        $this->trackManagedTenantDatabase(
            $tenant->database
        );

        $this->assertTrue(
            $this->tenantDatabaseExists(
                $tenant->database
            )
        );

        $this->assertStringStartsWith(
            'tenant_',
            $tenant->database
        );

        $admin = $this->runInTenant(
            $tenant,
            function () use (
                $adminEmail
            ): array {
                $this->assertTrue(
                    Schema::connection('tenant')
                        ->hasTable('users')
                );

                $user = TenantUser::query()
                    ->where('email', $adminEmail)
                    ->firstOrFail();

                return [
                    'name' => $user->name,
                    'role' => $user->role,
                    'is_active' => (bool) $user->is_active,
                    'password' => $user->getAuthPassword(),
                ];
            }
        );

        $this->assertSame(
            'Provisioned Administrator',
            $admin['name']
        );

        $this->assertSame('admin', $admin['role']);
        $this->assertTrue($admin['is_active']);

        $this->assertTrue(
            Hash::check(
                $adminPassword,
                $admin['password']
            )
        );
    }

    public function test_tenant_creation_returns_the_existing_validation_contract(): void
    {
        $headers = $this->superadminHeaders();

        $response = $this
            ->withHeaders($headers)
            ->postJson('/api/landlord/tenants', []);

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'message',
                'Validation failed'
            );

        $errors = $response->json('errors');

        $this->assertIsArray($errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('domain', $errors);
        $this->assertArrayHasKey(
            'admin_name',
            $errors
        );
        $this->assertArrayHasKey(
            'admin_email',
            $errors
        );
        $this->assertArrayHasKey(
            'admin_password',
            $errors
        );
    }
}