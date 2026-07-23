<?php

namespace Tests\Feature\Landlord;

use Tests\Support\Landlord\LandlordTenantUserTestCase;

class LandlordTenantUserReadContractTest extends
    LandlordTenantUserTestCase
{
    public function test_search_pagination_and_show_contracts_are_preserved(): void
    {
        $registration =
            $this->registerLandlordTestTenant();

        $tenant = $registration['tenant'];
        $headers = $this->superadminHeaders();

        $createResponse = $this->createTenantUser(
            headers: $headers,
            tenantId: (int) $tenant->id,
            attributes: [
                'name' => 'Needle Search User',
                'email' =>
                    'needle-search@example.test',
            ]
        );

        $createResponse->assertCreated();

        $userId = $createResponse->json('user.id');

        $this->assertIsInt($userId);

        $this->createTenantUser(
            headers: $headers,
            tenantId: (int) $tenant->id,
            attributes: [
                'name' => 'Unrelated User',
                'email' =>
                    'unrelated-user@example.test',
            ]
        )->assertCreated();

        $listResponse = $this
            ->withHeaders($headers)
            ->getJson(
                '/api/landlord/users'
                .'?tenant_id='.$tenant->id
                .'&search=Needle'
                .'&per_page=1'
                .'&page=1'
            );

        $listResponse
            ->assertOk()
            ->assertJsonPath('total_tenants', 1)
            ->assertJsonPath('users.total', 1)
            ->assertJsonPath('users.per_page', 1)
            ->assertJsonPath(
                'users.current_page',
                1
            )
            ->assertJsonPath(
                'users.last_page',
                1
            )
            ->assertJsonCount(1, 'users.data')
            ->assertJsonPath(
                'users.data.0.email',
                'needle-search@example.test'
            );

        $roles = $listResponse->json('roles');

        $this->assertIsArray($roles);
        $this->assertArrayNotHasKey(
            'superadmin',
            $roles
        );

        $this
            ->withHeaders($headers)
            ->getJson(
                '/api/landlord/users/'
                .$userId
                .'?tenant_id='
                .$tenant->id
            )
            ->assertOk()
            ->assertJsonPath(
                'user.user_key',
                $tenant->id.':'.$userId
            )
            ->assertJsonPath(
                'user.tenant_id',
                $tenant->id
            )
            ->assertJsonPath(
                'user.email',
                'needle-search@example.test'
            )
            ->assertJsonPath(
                'roles.doctor',
                'Doctor - View patient data and approve results'
            );
    }

    public function test_selected_tenant_user_not_found_contract_is_preserved(): void
    {
        $registration =
            $this->registerLandlordTestTenant();

        $headers = $this->superadminHeaders();

        $this
            ->withHeaders($headers)
            ->getJson(
                '/api/landlord/users/999999'
                .'?tenant_id='
                .$registration['tenant']->id
            )
            ->assertNotFound()
            ->assertJsonPath(
                'message',
                'User not found in the selected tenant.'
            );
    }
}