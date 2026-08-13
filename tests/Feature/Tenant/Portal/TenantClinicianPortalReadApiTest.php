<?php

namespace Tests\Feature\Tenant\Portal;

use Tests\Support\Tenant\PortalReadApiTestCase;

class TenantClinicianPortalReadApiTest extends PortalReadApiTestCase
{
    public function test_clinician_patients_and_orders_require_explicit_durable_linkage(): void
    {
        $tenant = $this->registerTestTenant(
            'clinician-portal-orders'
        );

        $clinicianA = $this->createClinicianPortalIdentity(
            $tenant['tenant']
        );

        $clinicianB = $this->createClinicianPortalIdentity(
            $tenant['tenant']
        );

        $patientA = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );

        $patientB = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );

        $linkedOrder = $this->createPortalOrder(
            $tenant['tenant'],
            $patientA['patient'],
            $clinicianA['clinician']
        );

        $freeTextOnlyOrder = $this->createPortalOrder(
            $tenant['tenant'],
            $patientB['patient'],
            null,
            [
                'referring_clinician' => (
                    $clinicianA['clinician']->name
                ),
            ]
        );

        $otherClinicianOrder = $this->createPortalOrder(
            $tenant['tenant'],
            $patientB['patient'],
            $clinicianB['clinician']
        );

        $token = $this->loginClinicianPortal(
            $tenant,
            $clinicianA
        );

        $headers = $this->portalHeaders(
            $token,
            $tenant['domain']
        );

        $patients = $this->withHeaders($headers)
            ->getJson('/api/portal/clinician/patients')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.0.medical_record_number',
                $patientA['patient']
                    ->medical_record_number
            );

        $patientPayload = $patients->json('data.0');

        $this->assertIsArray($patientPayload);
        $this->assertArrayNotHasKey(
            'email',
            $patientPayload
        );
        $this->assertArrayNotHasKey(
            'phone',
            $patientPayload
        );
        $this->assertArrayNotHasKey(
            'address',
            $patientPayload
        );

        $orders = $this->withHeaders($headers)
            ->getJson('/api/portal/clinician/orders')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.0.uuid',
                $linkedOrder->uuid
            );

        $this->assertStringNotContainsString(
            $freeTextOnlyOrder->uuid,
            $orders->getContent()
        );
        $this->assertStringNotContainsString(
            $otherClinicianOrder->uuid,
            $orders->getContent()
        );

        $this->withHeaders($headers)
            ->getJson(
                '/api/portal/clinician/orders/'
                .$freeTextOnlyOrder->uuid
            )
            ->assertNotFound();

        $this->withHeaders($headers)
            ->getJson(
                '/api/portal/clinician/orders/'
                .$otherClinicianOrder->uuid
            )
            ->assertNotFound();
    }

    public function test_clinician_sees_only_current_reports_for_explicitly_linked_orders(): void
    {
        $tenant = $this->registerTestTenant(
            'clinician-portal-reports'
        );

        $clinicianA = $this->createClinicianPortalIdentity(
            $tenant['tenant']
        );

        $clinicianB = $this->createClinicianPortalIdentity(
            $tenant['tenant']
        );

        $patientA = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );

        $patientB = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );

        $linkedOrder = $this->createPortalOrder(
            $tenant['tenant'],
            $patientA['patient'],
            $clinicianA['clinician']
        );

        $freeTextOnlyOrder = $this->createPortalOrder(
            $tenant['tenant'],
            $patientB['patient'],
            null,
            [
                'referring_clinician' => (
                    $clinicianA['clinician']->name
                ),
            ]
        );

        $otherClinicianOrder = $this->createPortalOrder(
            $tenant['tenant'],
            $patientB['patient'],
            $clinicianB['clinician']
        );

        $original = $this->createPortalReport(
            $tenant['tenant'],
            $linkedOrder
        );

        $amended = $this->createPortalReport(
            $tenant['tenant'],
            $linkedOrder,
            2,
            $original
        );

        $freeTextReport = $this->createPortalReport(
            $tenant['tenant'],
            $freeTextOnlyOrder
        );

        $otherClinicianReport = $this->createPortalReport(
            $tenant['tenant'],
            $otherClinicianOrder
        );

        $token = $this->loginClinicianPortal(
            $tenant,
            $clinicianA
        );

        $headers = $this->portalHeaders(
            $token,
            $tenant['domain']
        );

        $list = $this->withHeaders($headers)
            ->getJson('/api/portal/clinician/reports')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath(
                'data.0.uuid',
                $amended->uuid
            )
            ->assertJsonPath(
                'data.0.lifecycle_status',
                'amended'
            );

        foreach (
            [
                $original->uuid,
                $freeTextReport->uuid,
                $otherClinicianReport->uuid,
            ] as $forbiddenUuid
        ) {
            $this->assertStringNotContainsString(
                $forbiddenUuid,
                $list->getContent()
            );

            $this->withHeaders($headers)
                ->getJson(
                    '/api/portal/clinician/reports/'
                    .$forbiddenUuid
                )
                ->assertNotFound();
        }

        $this->withHeaders($headers)
            ->getJson(
                '/api/portal/clinician/reports/'
                .$amended->uuid
            )
            ->assertOk()
            ->assertJsonPath(
                'data.patient.medical_record_number',
                $patientA['patient']
                    ->medical_record_number
            )
            ->assertJsonPath(
                'data.results.0.results_data.0.value',
                '7'
            );
    }
}
