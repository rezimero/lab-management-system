<?php

namespace Tests\Support\Tenant;

use App\Models\LabTest;
use App\Models\Tenant;
use Illuminate\Support\Str;

abstract class LabTestManagementTestCase extends UserManagementTestCase
{
    /**
     * Register and authenticate a tenant for catalogue tests.
     *
     * @return array{
     *     registration: array<string, mixed>,
     *     tenant: Tenant,
     *     headers: array<string, string>
     * }
     */
    protected function authenticatedLabTestContext(
        string $prefix = 'lab-test'
    ): array {
        $registration = $this->registerTestTenant(
            $prefix
        );

        $token = $this->loginRegisteredTenant(
            $registration
        );

        return [
            'registration' => $registration,
            'tenant' => $registration['tenant'],
            'headers' => $this->tenantHeaders(
                $token,
                $registration['domain']
            ),
        ];
    }

    /**
     * Create a laboratory test directly in a tenant database.
     */
    protected function createLabTest(
        Tenant $tenant,
        array $attributes = []
    ): LabTest {
        return $this->runInTenant(
            $tenant,
            function () use ($attributes): LabTest {
                $defaults = [
                    'name' => sprintf(
                        'Laboratory Test %s',
                        strtoupper(Str::random(8))
                    ),
                    'description' => null,
                    'category' => 'General',
                    'price' => 0,
                    'turnaround_time_hours' => null,
                    'parameters' => null,
                    'is_active' => true,
                ];

                return LabTest::query()
                    ->create(
                        array_merge(
                            $defaults,
                            $attributes
                        )
                    )
                    ->fresh();
            }
        );
    }

    /**
     * Find a laboratory test in a tenant database.
     */
    protected function findLabTest(
        Tenant $tenant,
        int $id
    ): ?LabTest {
        return $this->runInTenant(
            $tenant,
            static fn (): ?LabTest => LabTest::query()
                ->find($id)
        );
    }
}