<?php

namespace Tests\Feature\Tenant\Portal;

use Tests\Support\Tenant\PortalAuthenticationTestCase;

class TenantPortalAuthenticationIsolationTest extends PortalAuthenticationTestCase
{
    public function test_same_patient_login_email_can_exist_in_multiple_tenants(): void
    {
        $tenantA = $this->registerTestTenant('portal-same-email-a');
        $tenantB = $this->registerTestTenant('portal-same-email-b');

        $email = 'shared.patient@example.test';

        $patientA = $this->createPatientPortalIdentity(
            $tenantA['tenant'],
            $email,
            'TenantAPassword123!'
        );

        $patientB = $this->createPatientPortalIdentity(
            $tenantB['tenant'],
            $email,
            'TenantBPassword123!'
        );

        $loginA = $this->postJson(
            '/api/portal/patient/login',
            [
                'domain' => $tenantA['domain'],
                'email' => $email,
                'password' => $patientA['password'],
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'patient.medical_record_number',
                $patientA['patient']
                    ->medical_record_number
            );

        $loginB = $this->postJson(
            '/api/portal/patient/login',
            [
                'domain' => $tenantB['domain'],
                'email' => $email,
                'password' => $patientB['password'],
            ]
        )
            ->assertOk()
            ->assertJsonPath(
                'patient.medical_record_number',
                $patientB['patient']
                    ->medical_record_number
            );

        $this->assertNotSame(
            $loginA->json('token'),
            $loginB->json('token')
        );

        $this->postJson(
            '/api/portal/patient/login',
            [
                'domain' => $tenantB['domain'],
                'email' => $email,
                'password' => $patientA['password'],
            ]
        )
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.email.0',
                'Invalid credentials.'
            );
    }

    public function test_patient_portal_token_cannot_cross_tenant_boundary(): void
    {
        $tenantA = $this->registerTestTenant('portal-patient-a');
        $tenantB = $this->registerTestTenant('portal-patient-b');

        $identity = $this->createPatientPortalIdentity(
            $tenantA['tenant']
        );

        $token = $this->postJson(
            '/api/portal/patient/login',
            [
                'domain' => $tenantA['domain'],
                'email' => $identity['email'],
                'password' => $identity['password'],
            ]
        )
            ->assertOk()
            ->json('token');

        $this->withHeaders(
            $this->portalHeaders(
                (string) $token,
                $tenantB['domain']
            )
        )
            ->getJson('/api/portal/patient/me')
            ->assertUnauthorized();
    }

    public function test_clinician_portal_token_cannot_cross_tenant_boundary(): void
    {
        $tenantA = $this->registerTestTenant('portal-clinician-a');
        $tenantB = $this->registerTestTenant('portal-clinician-b');

        $identity = $this->createClinicianPortalIdentity(
            $tenantA['tenant']
        );

        $token = $this->postJson(
            '/api/portal/clinician/login',
            [
                'domain' => $tenantA['domain'],
                'email' => $identity['email'],
                'password' => $identity['password'],
            ]
        )
            ->assertOk()
            ->json('token');

        $this->withHeaders(
            $this->portalHeaders(
                (string) $token,
                $tenantB['domain']
            )
        )
            ->getJson('/api/portal/clinician/me')
            ->assertUnauthorized();
    }

    public function test_portal_tokens_cannot_authenticate_internal_tenant_routes(): void
    {
        $tenant = $this->registerTestTenant('portal-internal-boundary');

        $patient = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );

        $portalToken = $this->postJson(
            '/api/portal/patient/login',
            [
                'domain' => $tenant['domain'],
                'email' => $patient['email'],
                'password' => $patient['password'],
            ]
        )
            ->assertOk()
            ->json('token');

        $this->withHeaders(
            $this->portalHeaders(
                (string) $portalToken,
                $tenant['domain']
            )
        )
            ->getJson('/api/patients')
            ->assertUnauthorized();
    }

    public function test_internal_sanctum_tokens_cannot_authenticate_portal_routes(): void
    {
        $tenant = $this->registerTestTenant('portal-sanctum-boundary');

        $this->withHeaders(
            $this->tenantHeaders(
                $tenant['token'],
                $tenant['domain']
            )
        )
            ->getJson('/api/portal/patient/me')
            ->assertUnauthorized();

        $this->withHeaders(
            $this->tenantHeaders(
                $tenant['token'],
                $tenant['domain']
            )
        )
            ->getJson('/api/portal/clinician/me')
            ->assertUnauthorized();
    }

    public function test_patient_and_clinician_portal_tokens_are_not_interchangeable(): void
    {
        $tenant = $this->registerTestTenant('portal-type-boundary');

        $patient = $this->createPatientPortalIdentity(
            $tenant['tenant']
        );
        $clinician = $this->createClinicianPortalIdentity(
            $tenant['tenant']
        );

        $patientToken = $this->postJson(
            '/api/portal/patient/login',
            [
                'domain' => $tenant['domain'],
                'email' => $patient['email'],
                'password' => $patient['password'],
            ]
        )
            ->assertOk()
            ->json('token');

        $clinicianToken = $this->postJson(
            '/api/portal/clinician/login',
            [
                'domain' => $tenant['domain'],
                'email' => $clinician['email'],
                'password' => $clinician['password'],
            ]
        )
            ->assertOk()
            ->json('token');

        $this->withHeaders(
            $this->portalHeaders(
                (string) $patientToken,
                $tenant['domain']
            )
        )
            ->getJson('/api/portal/clinician/me')
            ->assertUnauthorized();

        $this->withHeaders(
            $this->portalHeaders(
                (string) $clinicianToken,
                $tenant['domain']
            )
        )
            ->getJson('/api/portal/patient/me')
            ->assertUnauthorized();
    }
}
