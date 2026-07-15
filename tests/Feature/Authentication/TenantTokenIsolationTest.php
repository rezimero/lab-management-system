<?php

namespace Tests\Feature\Authentication;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\DB;
use Tests\Support\Authentication\TenantAuthenticationTestCase;

class TenantTokenIsolationTest extends TenantAuthenticationTestCase
{
    public function test_tenant_token_cannot_access_another_tenant(): void
    {
        $tenantA = $this->registerTestTenant();
        $tenantB = $this->registerTestTenant();

        $this->withHeaders(
            $this->tenantHeaders(
                $tenantA['token'],
                $tenantB['domain']
            )
        )
            ->getJson('/api/departments')
            ->assertUnauthorized();
    }

    public function test_tenant_token_is_bound_to_its_landlord_tenant_record(): void
    {
        $tenantA = $this->registerTestTenant();
        $tenantB = $this->registerTestTenant();

        $tenantUserType = (
            new TenantUser()
        )->getMorphClass();

        $this->assertDatabaseHas(
            'personal_access_tokens',
            [
                'tenant_id' => $tenantA['tenant']->id,
                'tokenable_type' => $tenantUserType,
                'tokenable_id' => 1,
            ],
            'landlord'
        );

        $this->assertDatabaseHas(
            'personal_access_tokens',
            [
                'tenant_id' => $tenantB['tenant']->id,
                'tokenable_type' => $tenantUserType,
                'tokenable_id' => 1,
            ],
            'landlord'
        );

        $tenantIds = DB::connection('landlord')
            ->table('personal_access_tokens')
            ->where('tokenable_type', $tenantUserType)
            ->where('tokenable_id', 1)
            ->whereIn(
                'tenant_id',
                [
                    $tenantA['tenant']->id,
                    $tenantB['tenant']->id,
                ]
            )
            ->pluck('tenant_id')
            ->map(
                static fn ($tenantId): int =>
                    (int) $tenantId
            )
            ->unique()
            ->sort()
            ->values()
            ->all();

        $expectedTenantIds = [
            (int) $tenantA['tenant']->id,
            (int) $tenantB['tenant']->id,
        ];

        sort($expectedTenantIds);

        $this->assertSame(
            $expectedTenantIds,
            $tenantIds
        );
    }
}