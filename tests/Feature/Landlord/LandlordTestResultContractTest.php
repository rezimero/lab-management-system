<?php

namespace Tests\Feature\Landlord;

use App\Models\TestResult;
use Tests\Support\Authentication\Concerns\ManagesTestLandlordUsers;
use Tests\Support\Tenant\TestResultManagementTestCase;

class LandlordTestResultContractTest extends
    TestResultManagementTestCase
{
    use ManagesTestLandlordUsers;

    protected const PASSWORD = 'Password123!';

    protected function tearDown(): void
    {
        try {
            $this->restoreLandlordContext();
            $this->cleanupTestLandlordUsers();
        } finally {
            parent::tearDown();
        }
    }

    public function test_result_index_show_and_delete_contracts_are_preserved(): void
    {
        $context = $this->authenticatedTestResultContext(
            'landlord-result-contract'
        );

        $tenant = $context['tenant'];
        $tenantHeaders = $context['headers'];
        $order = $this->createProcessingOrder(
            $tenant,
            $tenantHeaders
        );

        $item = $order->items->firstOrFail();

        $this
            ->startOrderItem($tenantHeaders, $item)
            ->assertOk();

        $draftResponse = $this
            ->createDraftResult(
                $tenantHeaders,
                $item
            );

        $draftResponse->assertCreated();

        $resultUuid = $draftResponse->json('data.uuid');

        $this->assertIsString($resultUuid);
        $this->assertNotSame('', $resultUuid);

        $result = $this->storedTestResult(
            $tenant,
            $resultUuid
        );

        $headers = $this->superadminHeaders();

        $indexResponse = $this
            ->withHeaders($headers)
            ->getJson(
                '/api/landlord/test-results'
                .'?search=draft'
                .'&per_page=1'
                .'&page=1'
            );

        $indexResponse
            ->assertOk()
            ->assertJsonPath('test_results.total', 1)
            ->assertJsonPath('test_results.per_page', 1)
            ->assertJsonPath('test_results.current_page', 1)
            ->assertJsonPath('test_results.last_page', 1)
            ->assertJsonCount(1, 'test_results.data')
            ->assertJsonPath(
                'test_results.data.0.id',
                $result->id
            )
            ->assertJsonPath(
                'test_results.data.0.uuid',
                $result->uuid
            )
            ->assertJsonPath(
                'test_results.data.0.status',
                TestResult::STATUS_DRAFT
            )
            ->assertJsonPath(
                'test_results.data.0.tenant_name',
                $tenant->name
            )
            ->assertJsonPath(
                'test_results.data.0.tenant_domain',
                $tenant->domain
            );

        $this->assertGreaterThanOrEqual(
            1,
            (int) $indexResponse->json('total_tenants')
        );

        $this->restoreLandlordContext();

        $this
            ->withHeaders($headers)
            ->getJson('/api/landlord/test-results/'.$result->id)
            ->assertOk()
            ->assertJsonPath('id', $result->id)
            ->assertJsonPath('uuid', $result->uuid)
            ->assertJsonPath('status', TestResult::STATUS_DRAFT)
            ->assertJsonPath('tenant_name', $tenant->name)
            ->assertJsonPath('tenant_domain', $tenant->domain);

        $this->restoreLandlordContext();

        $this
            ->withHeaders($headers)
            ->deleteJson('/api/landlord/test-results/'.$result->id)
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Test result deleted successfully'
            )
            ->assertJsonPath('tenant.id', $tenant->id)
            ->assertJsonPath('tenant.name', $tenant->name)
            ->assertJsonPath('tenant.domain', $tenant->domain);

        $this->restoreLandlordContext();

        $this->assertNull(
            $this->runInTenant(
                $tenant,
                static fn (): ?TestResult => TestResult::query()
                    ->find($result->id)
            )
        );

        $this
            ->withHeaders($headers)
            ->getJson('/api/landlord/test-results/'.$result->id)
            ->assertNotFound()
            ->assertJsonPath('message', 'Test result not found');
    }

    /**
     * @return array<string, string>
     */
    private function superadminHeaders(): array
    {
        $superadmin = $this->createTestLandlordUser(
            role: 'superadmin',
            isActive: true
        );

        $response = $this->loginLandlord($superadmin);

        $response
            ->assertOk()
            ->assertJsonPath('guard', 'landlord')
            ->assertJsonPath('is_superadmin', true);

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        return $this->bearerHeaders($token);
    }
}
