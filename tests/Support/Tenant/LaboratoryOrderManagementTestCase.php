<?php

namespace Tests\Support\Tenant;

use App\Models\LaboratoryOrder;
use App\Models\Patient;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
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
     * Create an order directly inside a tenant database.
     *
     * @param array<string, mixed> $attributes
     * @param array<int, array<string, mixed>> $items
     */
    protected function createLaboratoryOrder(
        Tenant $tenant,
        Patient $patient,
        array $attributes = [],
        array $items = []
    ): LaboratoryOrder {
        return $this->runInTenant(
            $tenant,
            function () use (
                $patient,
                $attributes,
                $items
            ): LaboratoryOrder {
                $order = LaboratoryOrder::query()
                    ->create(
                        array_merge(
                            [
                                'order_number' => (
                                    'LAB-TEST-'
                                    .strtoupper(
                                        Str::random(12)
                                    )
                                ),
                                'patient_id' => $patient->id,
                                'ordered_by' => null,
                                'referring_clinician' => null,
                                'priority' => 'routine',
                                'status' => 'pending',
                                'clinical_notes' => null,
                                'total_amount' => '0.00',
                                'ordered_at' => now(),
                            ],
                            $attributes
                        )
                    );

                foreach ($items as $item) {
                    $order->items()->create(
                        array_merge(
                            [
                                'lab_test_id' => null,
                                'test_name_snapshot' => (
                                    'Laboratory Test'
                                ),
                                'test_category_snapshot' => (
                                    'General'
                                ),
                                'price_snapshot' => '0.00',
                                'turnaround_time_hours_snapshot' => null,
                                'parameters_snapshot' => null,
                                'status' => 'pending',
                            ],
                            $item
                        )
                    );
                }

                return $order
                    ->fresh()
                    ->load([
                        'patient',
                        'orderedBy',
                        'items',
                    ]);
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
                (int) DB::connection('tenant')
                    ->table($table)
                    ->count()
            )
        );
    }
}
