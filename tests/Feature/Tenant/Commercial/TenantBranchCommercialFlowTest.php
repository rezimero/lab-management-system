<?php

namespace Tests\Feature\Tenant\Commercial;

use Tests\Support\Tenant\LaboratoryOrderManagementTestCase;

class TenantBranchCommercialFlowTest extends LaboratoryOrderManagementTestCase
{
    public function test_administrator_manages_branch_network_and_routes_an_order(): void
    {
        $context = $this->authenticatedLaboratoryOrderContext(
            'commercial-branch'
        );

        $headOfficeResponse = $this
            ->withHeaders($context['headers'])
            ->getJson('/api/branches?per_page=50');

        $headOfficeResponse
            ->assertOk()
            ->assertJsonPath('data.0.code', 'HO')
            ->assertJsonPath('data.0.is_head_office', true)
            ->assertJsonPath('data.0.type', 'laboratory');

        $laboratory = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/branches', [
                'code' => 'LAB-NORTH',
                'name' => 'North Processing Laboratory',
                'type' => 'laboratory',
                'city' => 'Abuja',
                'state' => 'FCT',
                'country' => 'Nigeria',
                'is_active' => true,
            ]);

        $laboratory
            ->assertCreated()
            ->assertJsonPath('data.code', 'LAB-NORTH')
            ->assertJsonPath('data.type', 'laboratory');

        $laboratoryUuid = $laboratory->json('data.uuid');

        $centre = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/branches', [
                'code' => 'CC-WUSE',
                'name' => 'Wuse Collection Centre',
                'type' => 'collection_centre',
                'parent_branch_uuid' => $laboratoryUuid,
                'city' => 'Abuja',
                'state' => 'FCT',
                'country' => 'Nigeria',
                'is_active' => true,
            ]);

        $centre
            ->assertCreated()
            ->assertJsonPath('data.code', 'CC-WUSE')
            ->assertJsonPath('data.parent.uuid', $laboratoryUuid);

        $centreUuid = $centre->json('data.uuid');

        $patient = $this->createPatient($context['tenant']);
        $labTest = $this->createLabTest(
            $context['tenant'],
            ['price' => '12500.00']
        );

        $order = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/laboratory-orders', [
                'patient_id' => $patient->getKey(),
                'collection_branch_uuid' => $centreUuid,
                'processing_branch_uuid' => $laboratoryUuid,
                'priority' => 'routine',
                'items' => [
                    ['lab_test_id' => $labTest->getKey()],
                ],
            ]);

        $order
            ->assertCreated()
            ->assertJsonPath('data.collection_branch.uuid', $centreUuid)
            ->assertJsonPath('data.processing_branch.uuid', $laboratoryUuid)
            ->assertJsonPath('data.total_amount', '12500.00');
    }

    public function test_collection_centre_requires_an_active_processing_laboratory(): void
    {
        $context = $this->authenticatedLaboratoryOrderContext(
            'commercial-centre-parent'
        );

        $this->withHeaders($context['headers'])
            ->postJson('/api/branches', [
                'code' => 'CC-NO-PARENT',
                'name' => 'Unrouted Collection Centre',
                'type' => 'collection_centre',
                'is_active' => true,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('parent_branch_uuid');
    }

    public function test_processing_laboratory_with_active_collection_centre_cannot_be_deactivated(): void
    {
        $context = $this->authenticatedLaboratoryOrderContext(
            'commercial-branch-deactivation'
        );

        $laboratoryUuid = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/branches', [
                'code' => 'LAB-DEACTIVATE',
                'name' => 'Protected Processing Laboratory',
                'type' => 'laboratory',
                'is_active' => true,
            ])
            ->assertCreated()
            ->json('data.uuid');

        $this->withHeaders($context['headers'])
            ->postJson('/api/branches', [
                'code' => 'CC-DEACTIVATE',
                'name' => 'Dependent Collection Centre',
                'type' => 'collection_centre',
                'parent_branch_uuid' => $laboratoryUuid,
                'is_active' => true,
            ])
            ->assertCreated();

        $this->withHeaders($context['headers'])
            ->patchJson("/api/branches/{$laboratoryUuid}/deactivate")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('branch');
    }
}
