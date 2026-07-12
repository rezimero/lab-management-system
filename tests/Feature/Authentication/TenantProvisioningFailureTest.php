<?php

namespace Tests\Feature\Authentication;

use App\Services\Authentication\AuthenticationService;
use Mockery\MockInterface;
use RuntimeException;
use Tests\Support\Authentication\TenantAuthenticationTestCase;

class TenantProvisioningFailureTest extends TenantAuthenticationTestCase
{
    public function test_failed_provisioning_removes_tenant_and_database(): void
    {
        $domain = $this->uniqueTenantDomain('failed');
        $email = $domain.'@example.test';

        $this->trackTenantDomain($domain);

        $this->mock(
            AuthenticationService::class,
            function (MockInterface $mock): void {
                $mock->shouldReceive('createToken')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'Forced token creation failure.'
                        )
                    );
            }
        );

        $this->postJson('/api/register', [
            'name' => 'Failed Tenant Administrator',
            'email' => $email,
            'password' => 'password',
            'password_confirmation' => 'password',
            'tenant_name' => 'Failed Provisioning Laboratory',
            'tenant_domain' => $domain,
        ])->assertStatus(500);

        $this->assertDatabaseMissing(
            'tenants',
            [
                'domain' => $domain,
            ],
            'landlord'
        );

        $this->assertSame(
            [],
            $this->tenantDatabasesForDomain($domain),
            'Failed provisioning left an orphan tenant database.'
        );
    }
}