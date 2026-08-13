<?php

namespace Tests\Feature\Tenant\Portal;

use Tests\Support\Tenant\PortalReadApiTestCase;

class TenantPatientPortalReadApiTest extends PortalReadApiTestCase
{
    public function test_patient_sees_only_own_orders_without_internal_result_workflow_data(): void
    {
        $tenant = $this->registerTestTenant(
            'patient-portal-orders'
        );

        $patientA = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );

        $patientB = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );

        $ownOrder = $this->createPortalOrder(
            $tenant['tenant'],
            $patientA['patient']
        );

        $foreignOrder = $this->createPortalOrder(
            $tenant['tenant'],
            $patientB['patient']
        );

        $this->addInternalResult(
            $tenant['tenant'],
            $ownOrder,
            'draft',
            'SECRET-DRAFT-VALUE'
        );

        $token = $this->loginPatientPortal(
            $tenant,
            $patientA
        );

        $headers = $this->portalHeaders(
            $token,
            $tenant['domain']
        );

        $response = $this->withHeaders($headers)
            ->getJson('/api/portal/patient/orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.0.uuid',
                $ownOrder->uuid
            )
            ->assertJsonMissing([
                'uuid' => $foreignOrder->uuid,
            ]);

        $payload = $response->json('data.0');

        $this->assertIsArray($payload);
        $this->assertArrayNotHasKey(
            'clinical_notes',
            $payload
        );
        $this->assertArrayNotHasKey(
            'total_amount',
            $payload
        );
        $this->assertArrayNotHasKey(
            'ordered_by',
            $payload
        );

        $item = $payload['items'][0] ?? null;

        $this->assertIsArray($item);
        $this->assertArrayNotHasKey(
            'price_snapshot',
            $item
        );
        $this->assertArrayNotHasKey(
            'test_result',
            $item
        );

        $this->assertStringNotContainsString(
            'SECRET-DRAFT-VALUE',
            $response->getContent()
        );

        $this->withHeaders($headers)
            ->getJson(
                '/api/portal/patient/orders/'
                .$foreignOrder->uuid
            )
            ->assertNotFound();

        $this->withHeaders($headers)
            ->getJson('/api/portal/patient/reports')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_patient_sees_only_current_issued_or_amended_own_reports(): void
    {
        $tenant = $this->registerTestTenant(
            'patient-portal-reports'
        );

        $patientA = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );

        $patientB = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );

        $ownOrder = $this->createPortalOrder(
            $tenant['tenant'],
            $patientA['patient']
        );

        $foreignOrder = $this->createPortalOrder(
            $tenant['tenant'],
            $patientB['patient']
        );

        $original = $this->createPortalReport(
            $tenant['tenant'],
            $ownOrder
        );

        $amended = $this->createPortalReport(
            $tenant['tenant'],
            $ownOrder,
            2,
            $original
        );

        $foreign = $this->createPortalReport(
            $tenant['tenant'],
            $foreignOrder
        );

        $token = $this->loginPatientPortal(
            $tenant,
            $patientA
        );

        $headers = $this->portalHeaders(
            $token,
            $tenant['domain']
        );

        $list = $this->withHeaders($headers)
            ->getJson('/api/portal/patient/reports')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.0.uuid',
                $amended->uuid
            )
            ->assertJsonPath(
                'data.0.lifecycle_status',
                'amended'
            )
            ->assertJsonPath(
                'data.0.is_current',
                true
            );

        $this->assertStringNotContainsString(
            $original->uuid,
            $list->getContent()
        );
        $this->assertStringNotContainsString(
            $foreign->uuid,
            $list->getContent()
        );

        $detail = $this->withHeaders($headers)
            ->getJson(
                '/api/portal/patient/reports/'
                .$amended->uuid
            )
            ->assertOk()
            ->assertJsonPath(
                'data.results.0.results_data.0.value',
                '7'
            )
            ->assertJsonPath(
                'data.amendment.reason',
                'Corrected issued value'
            );

        $payload = $detail->json('data');

        $this->assertIsArray($payload);
        $this->assertArrayNotHasKey(
            'generated_by',
            $payload
        );
        $this->assertArrayNotHasKey(
            'lineage_uuid',
            $payload
        );

        $laboratory = $payload['laboratory'] ?? [];

        $this->assertArrayNotHasKey(
            'tin',
            $laboratory
        );
        $this->assertArrayNotHasKey(
            'logo_url',
            $laboratory
        );
        $this->assertArrayNotHasKey(
            'director_signature_url',
            $laboratory
        );

        $order = $payload['order'] ?? [];

        $this->assertArrayNotHasKey(
            'clinical_notes',
            $order
        );
        $this->assertArrayNotHasKey(
            'total_amount',
            $order
        );
        $this->assertArrayNotHasKey(
            'ordered_by',
            $order
        );

        $patient = $payload['patient'] ?? [];

        $this->assertArrayNotHasKey(
            'email',
            $patient
        );
        $this->assertArrayNotHasKey(
            'phone',
            $patient
        );
        $this->assertArrayNotHasKey(
            'address',
            $patient
        );

        $result = $payload['results'][0] ?? [];

        $this->assertArrayNotHasKey(
            'result_uuid',
            $result
        );
        $this->assertArrayNotHasKey(
            'entered_by',
            $result
        );
        $this->assertArrayNotHasKey(
            'submitted_by',
            $result
        );
        $this->assertArrayNotHasKey(
            'reviewed_by',
            $result
        );
        $this->assertArrayNotHasKey(
            'result_amendment_uuid',
            $result
        );

        $amendment = $payload['amendment'] ?? [];

        $this->assertArrayNotHasKey(
            'summary',
            $amendment
        );
        $this->assertArrayNotHasKey(
            'actor',
            $amendment
        );

        $this->withHeaders($headers)
            ->getJson(
                '/api/portal/patient/reports/'
                .$original->uuid
            )
            ->assertNotFound();

        $this->withHeaders($headers)
            ->getJson(
                '/api/portal/patient/reports/'
                .$foreign->uuid
            )
            ->assertNotFound();
    }

    public function test_patient_read_endpoints_remain_tenant_isolated(): void
    {
        $tenantA = $this->registerTestTenant(
            'patient-portal-read-a'
        );

        $tenantB = $this->registerTestTenant(
            'patient-portal-read-b'
        );

        $patientA = $this->createPatientPortalIdentity(
            $tenantA['tenant']
        );

        $this->createPortalOrder(
            $tenantA['tenant'],
            $patientA['patient']
        );

        $token = $this->loginPatientPortal(
            $tenantA,
            $patientA
        );

        $this->withHeaders(
            $this->portalHeaders(
                $token,
                $tenantB['domain']
            )
        )
            ->getJson('/api/portal/patient/orders')
            ->assertUnauthorized();
    }
}
