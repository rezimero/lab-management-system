<?php

namespace Tests\Feature\Tenant\LaboratoryOrder;

use App\Models\ReferringClinician;
use Tests\Support\Tenant\LaboratoryOrderManagementTestCase;

class TenantLaboratoryOrderClinicianLinkageTest extends LaboratoryOrderManagementTestCase
{
    public function test_new_order_can_link_to_active_tenant_referring_clinician(): void
    {
        $context = $this->authenticatedLaboratoryOrderContext(
            'order-clinician-link'
        );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $labTest = $this->createLabTest(
            $context['tenant']
        );

        $clinician = $this->runInTenant(
            $context['tenant'],
            fn (): ReferringClinician => (
                ReferringClinician::query()->create([
                    'name' => 'Dr Linked Clinician',
                    'email' => 'linked@example.test',
                    'organisation' => 'Linked Clinic',
                    'registration_number' => 'MDCN-LINK-1',
                    'specialty' => 'Family Medicine',
                    'is_active' => true,
                ])
            )
        );

        $response = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/laboratory-orders', [
                'patient_id' => $patient->id,
                'referring_clinician_uuid' => $clinician->uuid,
                'priority' => 'routine',
                'items' => [
                    [
                        'lab_test_id' => $labTest->id,
                    ],
                ],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.referring_clinician',
                'Dr Linked Clinician'
            )
            ->assertJsonPath(
                'data.referring_clinician_record.uuid',
                $clinician->uuid
            )
            ->assertJsonPath(
                'data.referring_clinician_record.organisation',
                'Linked Clinic'
            );

        $order = $this->findLaboratoryOrder(
            $context['tenant'],
            (string) $response->json('data.uuid')
        );

        $this->assertNotNull($order);
        $this->assertSame(
            $clinician->id,
            $order->referring_clinician_id
        );
        $this->assertSame(
            'Dr Linked Clinician',
            $order->referring_clinician
        );
    }

    public function test_linked_order_preserves_clinician_name_snapshot_after_directory_rename(): void
    {
        $context = $this->authenticatedLaboratoryOrderContext(
            'order-clinician-snapshot'
        );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $labTest = $this->createLabTest(
            $context['tenant']
        );

        $clinician = $this->runInTenant(
            $context['tenant'],
            fn (): ReferringClinician => (
                ReferringClinician::query()->create([
                    'name' => 'Dr Original Name',
                    'is_active' => true,
                ])
            )
        );

        $created = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/laboratory-orders', [
                'patient_id' => $patient->id,
                'referring_clinician_uuid' => $clinician->uuid,
                'items' => [
                    [
                        'lab_test_id' => $labTest->id,
                    ],
                ],
            ])
            ->assertCreated();

        $uuid = (string) $created->json('data.uuid');

        $this->runInTenant(
            $context['tenant'],
            function () use ($clinician): void {
                $clinician->update([
                    'name' => 'Dr Current Directory Name',
                ]);
            }
        );

        $this->withHeaders($context['headers'])
            ->getJson('/api/laboratory-orders/'.$uuid)
            ->assertOk()
            ->assertJsonPath(
                'data.referring_clinician',
                'Dr Original Name'
            )
            ->assertJsonPath(
                'data.referring_clinician_record.name',
                'Dr Current Directory Name'
            );
    }

    public function test_inactive_referring_clinician_cannot_be_linked_to_new_order(): void
    {
        $context = $this->authenticatedLaboratoryOrderContext(
            'order-clinician-inactive'
        );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $labTest = $this->createLabTest(
            $context['tenant']
        );

        $clinician = $this->runInTenant(
            $context['tenant'],
            fn (): ReferringClinician => (
                ReferringClinician::query()->create([
                    'name' => 'Dr Inactive',
                    'is_active' => false,
                ])
            )
        );

        $this->withHeaders($context['headers'])
            ->postJson('/api/laboratory-orders', [
                'patient_id' => $patient->id,
                'referring_clinician_uuid' => $clinician->uuid,
                'items' => [
                    [
                        'lab_test_id' => $labTest->id,
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.referring_clinician_uuid.0',
                'The selected referring clinician is unavailable or inactive.'
            );
    }

    public function test_referring_clinician_uuid_from_another_tenant_cannot_be_linked(): void
    {
        $context = $this->authenticatedLaboratoryOrderContext(
            'order-clinician-owner'
        );

        $foreign = $this->authenticatedLaboratoryOrderContext(
            'order-clinician-foreign'
        );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $labTest = $this->createLabTest(
            $context['tenant']
        );

        $foreignClinician = $this->runInTenant(
            $foreign['tenant'],
            fn (): ReferringClinician => (
                ReferringClinician::query()->create([
                    'name' => 'Dr Foreign Tenant',
                    'is_active' => true,
                ])
            )
        );

        $this->withHeaders($context['headers'])
            ->postJson('/api/laboratory-orders', [
                'patient_id' => $patient->id,
                'referring_clinician_uuid' => (
                    $foreignClinician->uuid
                ),
                'items' => [
                    [
                        'lab_test_id' => $labTest->id,
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'referring_clinician_uuid',
            ]);
    }

    public function test_legacy_free_text_referring_clinician_remains_backward_compatible(): void
    {
        $context = $this->authenticatedLaboratoryOrderContext(
            'order-clinician-legacy'
        );

        $patient = $this->createPatient(
            $context['tenant']
        );

        $labTest = $this->createLabTest(
            $context['tenant']
        );

        $response = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/laboratory-orders', [
                'patient_id' => $patient->id,
                'referring_clinician' => (
                    '  Dr Historical Text  '
                ),
                'items' => [
                    [
                        'lab_test_id' => $labTest->id,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath(
                'data.referring_clinician',
                'Dr Historical Text'
            )
            ->assertJsonPath(
                'data.referring_clinician_record',
                null
            );

        $order = $this->findLaboratoryOrder(
            $context['tenant'],
            (string) $response->json('data.uuid')
        );

        $this->assertNotNull($order);
        $this->assertNull(
            $order->referring_clinician_id
        );
    }
}
