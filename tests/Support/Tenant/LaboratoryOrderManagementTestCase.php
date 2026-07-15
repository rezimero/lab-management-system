<?php

namespace Tests\Support\Tenant;

use App\Models\LaboratoryOrder;
use App\Models\Patient;
use App\Models\Tenant;
use Illuminate\Support\Str;

abstract class LaboratoryOrderManagementTestCase extends LabTestManagementTestCase
{
    /**
     * Register and authenticate a tenant.
     *
     * @return array{
     *     registration: array<string, mixed>,
     *     tenant: Tenant,
     *     headers: array<string, string>
     * }
     */
    protected function authenticatedLaboratoryOrderContext(
        string $prefix = 'laboratory-order'
    ): array {
        return $this->authenticatedLabTestContext(
            $prefix
        );
    }

    /**
     * Create a patient inside a tenant database.
     */
    protected function createPatient(
        Tenant $tenant,
        array $attributes = []
    ): Patient {
        return $this->runInTenant(
            $tenant,
            function () use ($attributes): Patient {
                $defaults = [
                    'first_name' => 'Test',
                    'last_name' => 'Patient',
                    'date_of_birth' => '1990-01-01',
                    'gender' => 'other',
                    'email' => null,
                    'phone' => '08000000000',
                    'address' => null,
                    'medical_record_number' => (
                        'MRN-'
                        .strtoupper(
                            Str::random(12)
                        )
                    ),
                    'notes' => null,
                    'is_active' => true,
                ];

                return Patient::query()
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
     * Find an order by public UUID.
     */
    protected function findLaboratoryOrder(
        Tenant $tenant,
        string $uuid
    ): ?LaboratoryOrder {
        return $this->runInTenant(
            $tenant,
            static fn (): ?LaboratoryOrder => (
                LaboratoryOrder::query()
                    ->with('items')
                    ->where('uuid', $uuid)
                    ->first()
            )
        );
    }

    /**
     * Count rows in a tenant table.
     */
    protected function tenantTableCount(
        Tenant $tenant,
        string $table
    ): int {
        return $this->runInTenant(
            $tenant,
            static fn (): int => (
                (int) \Illuminate\Support\Facades\DB
                    ::connection('tenant')
                    ->table($table)
                    ->count()
            )
        );
    }
}
