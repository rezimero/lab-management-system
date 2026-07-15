<?php

namespace Tests\Feature\Landlord\Tenant;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\Hash;
use Tests\Support\Landlord\TenantManagementTestCase;

class LandlordTenantAdminPasswordTest extends TenantManagementTestCase
{
    public function test_superadmin_can_reset_a_tenant_admin_password(): void
    {
        $registration = $this->registerLandlordTestTenant();
        $tenant = $registration['tenant'];

        $adminEmail = $registration['domain']
            .'@example.test';

        $newPassword = 'NewPassword123!';

        $headers = $this->superadminHeaders();

        $response = $this
            ->withHeaders($headers)
            ->postJson(
                '/api/landlord/tenants/'
                .$tenant->id
                .'/reset-admin-password',
                [
                    'email' => $adminEmail,
                    'new_password' => $newPassword,
                ]
            );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Password reset successfully'
            )
            ->assertJsonPath(
                'user.email',
                $adminEmail
            );

        $this->restoreLandlordContext();

        $passwordHash = $this->runInTenant(
            $tenant,
            static function () use (
                $adminEmail
            ): string {
                return TenantUser::query()
                    ->where('email', $adminEmail)
                    ->firstOrFail()
                    ->getAuthPassword();
            }
        );

        $this->assertTrue(
            Hash::check(
                $newPassword,
                $passwordHash
            )
        );
    }
}