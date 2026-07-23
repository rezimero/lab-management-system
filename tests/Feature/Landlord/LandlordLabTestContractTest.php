<?php

namespace Tests\Feature\Landlord;

use App\Models\LabTest;
use App\Models\Tenant;
use Tests\Support\Landlord\TenantManagementTestCase;

class LandlordLabTestContractTest extends TenantManagementTestCase
{
    public function test_lab_test_index_show_and_delete_contracts_are_preserved(): void
    {
        $registration = $this->registerLandlordTestTenant();
        $tenant = $registration['tenant'];
        $headers = $this->superadminHeaders();

        $labTest = $this->createLabTest(
            $tenant,
            [
                'name' => 'Needle Chemistry Panel',
                'category' => 'Needle Category',
            ]
        );

        $indexResponse = $this
            ->withHeaders($headers)
            ->getJson(
                '/api/landlord/lab-tests'
                .'?search=Needle'
                .'&per_page=1'
                .'&page=1'
            );

        $indexResponse
            ->assertOk()
            ->assertJsonPath('lab_tests.total', 1)
            ->assertJsonPath('lab_tests.per_page', 1)
            ->assertJsonPath('lab_tests.current_page', 1)
            ->assertJsonPath('lab_tests.last_page', 1)
            ->assertJsonCount(1, 'lab_tests.data')
            ->assertJsonPath(
                'lab_tests.data.0.id',
                $labTest->id
            )
            ->assertJsonPath(
                'lab_tests.data.0.tenant_name',
                $tenant->name
            )
            ->assertJsonPath(
                'lab_tests.data.0.tenant_domain',
                $tenant->domain
            );

        $this->assertGreaterThanOrEqual(
            1,
            (int) $indexResponse->json('total_tenants')
        );

        $this->restoreLandlordContext();

        $this
            ->withHeaders($headers)
            ->getJson('/api/landlord/lab-tests/'.$labTest->id)
            ->assertOk()
            ->assertJsonPath('id', $labTest->id)
            ->assertJsonPath('name', 'Needle Chemistry Panel')
            ->assertJsonPath('category', 'Needle Category')
            ->assertJsonPath('tenant_name', $tenant->name)
            ->assertJsonPath('tenant_domain', $tenant->domain);

        $this->restoreLandlordContext();

        $this
            ->withHeaders($headers)
            ->deleteJson('/api/landlord/lab-tests/'.$labTest->id)
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Lab test deleted successfully'
            )
            ->assertJsonPath('tenant.id', $tenant->id)
            ->assertJsonPath('tenant.name', $tenant->name)
            ->assertJsonPath('tenant.domain', $tenant->domain);

        $this->restoreLandlordContext();

        $this->assertNull(
            $this->runInTenant(
                $tenant,
                static fn (): ?LabTest => LabTest::query()
                    ->find($labTest->id)
            )
        );

        $this
            ->withHeaders($headers)
            ->getJson('/api/landlord/lab-tests/'.$labTest->id)
            ->assertNotFound()
            ->assertJsonPath('message', 'Lab test not found');
    }

    /**
     * Create a catalogue test in the selected tenant database.
     *
     * @param array<string, mixed> $attributes
     */
    private function createLabTest(
        Tenant $tenant,
        array $attributes = []
    ): LabTest {
        return $this->runInTenant(
            $tenant,
            static function () use ($attributes): LabTest {
                return LabTest::query()->create(
                    array_merge(
                        [
                            'name' => 'Landlord Catalogue Test',
                            'description' => null,
                            'category' => 'General',
                            'price' => 2500,
                            'turnaround_time_hours' => 24,
                            'parameters' => null,
                            'is_active' => true,
                        ],
                        $attributes
                    )
                )->fresh();
            }
        );
    }
}
