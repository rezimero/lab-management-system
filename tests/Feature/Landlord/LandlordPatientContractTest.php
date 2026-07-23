<?php

namespace Tests\Feature\Landlord;

use App\Models\Patient;
use App\Models\Tenant;
use Tests\Support\Landlord\TenantManagementTestCase;

class LandlordPatientContractTest extends TenantManagementTestCase
{
    public function test_patient_index_show_and_delete_contracts_are_preserved(): void
    {
        $registration = $this->registerLandlordTestTenant();
        $tenant = $registration['tenant'];
        $headers = $this->superadminHeaders();

        $patient = $this->createPatient(
            $tenant,
            [
                'first_name' => 'Needle',
                'last_name' => 'Patient',
                'email' => 'needle-patient@example.test',
                'medical_record_number' => 'MRN-NEEDLE-001',
            ]
        );

        $indexResponse = $this
            ->withHeaders($headers)
            ->getJson(
                '/api/landlord/patients'
                .'?search=Needle'
                .'&per_page=1'
                .'&page=1'
            );

        $indexResponse
            ->assertOk()
            ->assertJsonPath('patients.total', 1)
            ->assertJsonPath('patients.per_page', 1)
            ->assertJsonPath('patients.current_page', 1)
            ->assertJsonPath('patients.last_page', 1)
            ->assertJsonCount(1, 'patients.data')
            ->assertJsonPath(
                'patients.data.0.id',
                $patient->id
            )
            ->assertJsonPath(
                'patients.data.0.tenant_name',
                $tenant->name
            )
            ->assertJsonPath(
                'patients.data.0.tenant_domain',
                $tenant->domain
            );

        $this->assertGreaterThanOrEqual(
            1,
            (int) $indexResponse->json('total_tenants')
        );

        $this->restoreLandlordContext();

        $this
            ->withHeaders($headers)
            ->getJson('/api/landlord/patients/'.$patient->id)
            ->assertOk()
            ->assertJsonPath('id', $patient->id)
            ->assertJsonPath('first_name', 'Needle')
            ->assertJsonPath('last_name', 'Patient')
            ->assertJsonPath('tenant_name', $tenant->name)
            ->assertJsonPath('tenant_domain', $tenant->domain);

        $this->restoreLandlordContext();

        $this
            ->withHeaders($headers)
            ->deleteJson('/api/landlord/patients/'.$patient->id)
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Patient deleted successfully'
            )
            ->assertJsonPath('tenant.id', $tenant->id)
            ->assertJsonPath('tenant.name', $tenant->name)
            ->assertJsonPath('tenant.domain', $tenant->domain);

        $this->restoreLandlordContext();

        $this->assertNull(
            $this->runInTenant(
                $tenant,
                static fn (): ?Patient => Patient::query()
                    ->find($patient->id)
            )
        );

        $this
            ->withHeaders($headers)
            ->getJson('/api/landlord/patients/'.$patient->id)
            ->assertNotFound()
            ->assertJsonPath('message', 'Patient not found');
    }

    /**
     * Create a patient in the selected tenant database.
     *
     * @param array<string, mixed> $attributes
     */
    private function createPatient(
        Tenant $tenant,
        array $attributes = []
    ): Patient {
        return $this->runInTenant(
            $tenant,
            static function () use ($attributes): Patient {
                return Patient::query()->create(
                    array_merge(
                        [
                            'first_name' => 'Landlord',
                            'last_name' => 'Patient',
                            'date_of_birth' => '1990-01-15',
                            'gender' => 'female',
                            'email' => 'patient@example.test',
                            'phone' => '+2348000000000',
                            'address' => 'Test address',
                            'medical_record_number' => 'MRN-DEFAULT-001',
                            'notes' => null,
                            'is_active' => true,
                        ],
                        $attributes
                    )
                )->fresh();
            }
        );
    }
}
