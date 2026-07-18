<?php

namespace Tests\Feature\Tenant\TestResult;

use App\Models\Tenant;
use Tests\Support\Tenant\TestResultManagementTestCase;

class TenantTestResultAuthorizationTest extends TestResultManagementTestCase
{
    public function test_tenant_result_role_permissions_are_enforced(): void
    {
        $context = $this
            ->authenticatedTestResultContext(
                'result-roles'
            );

        $receptionistHeaders = $this
            ->headersForTenantRole(
                $context['tenant'],
                $context['registration']['domain'],
                'receptionist'
            );

        $technicianHeaders = $this
            ->headersForTenantRole(
                $context['tenant'],
                $context['registration']['domain'],
                'lab_technician'
            );

        $doctorHeaders = $this
            ->headersForTenantRole(
                $context['tenant'],
                $context['registration']['domain'],
                'doctor'
            );

        $this->resetRequestAuthentication();

        $order = $this->createProcessingOrder(
            $context['tenant'],
            $context['headers']
        );

        $item = $order->items->firstOrFail();

        $this->resetRequestAuthentication();

        $this
            ->withHeaders($receptionistHeaders)
            ->getJson('/api/test-results')
            ->assertOk();

        $this->resetRequestAuthentication();

        $this->startOrderItem(
            $receptionistHeaders,
            $item
        )->assertForbidden();

        $this->resetRequestAuthentication();

        $this->startOrderItem(
            $doctorHeaders,
            $item
        )->assertForbidden();

        $this->resetRequestAuthentication();

        $this->startOrderItem(
            $technicianHeaders,
            $item
        )->assertOk();

        $this->resetRequestAuthentication();

        $createResponse = $this
            ->createDraftResult(
                $technicianHeaders,
                $item
            )
            ->assertCreated();

        $resultUuid = $createResponse->json(
            'data.uuid'
        );

        $this->assertIsString(
            $resultUuid
        );

        $this->resetRequestAuthentication();

        $this
            ->withHeaders($receptionistHeaders)
            ->getJson(
                '/api/test-results/'
                .$resultUuid
            )
            ->assertOk();

        $this->resetRequestAuthentication();

        $this
            ->withHeaders($receptionistHeaders)
            ->putJson(
                '/api/test-results/'
                .$resultUuid,
                [
                    'notes' => (
                        'Receptionists cannot edit.'
                    ),
                ]
            )
            ->assertForbidden();

        $this->resetRequestAuthentication();

        $this
            ->withHeaders($technicianHeaders)
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/approve'
            )
            ->assertForbidden();

        $this->resetRequestAuthentication();

        $this
            ->withHeaders($technicianHeaders)
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/submit'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'submitted'
            );

        $this->resetRequestAuthentication();

        $this
            ->withHeaders($doctorHeaders)
            ->putJson(
                '/api/test-results/'
                .$resultUuid,
                [
                    'notes' => (
                        'Doctors cannot edit results.'
                    ),
                ]
            )
            ->assertForbidden();

        $this->resetRequestAuthentication();

        $this
            ->withHeaders($doctorHeaders)
            ->postJson(
                '/api/test-results/'
                .$resultUuid
                .'/approve'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'approved'
            );
    }

    /**
     * Authenticate a temporary tenant user with a role.
     *
     * @return array<string, string>
     */
    private function headersForTenantRole(
        Tenant $tenant,
        string $domain,
        string $role
    ): array {
        $password = 'Password123!';

        $user = $this->createTenantUser(
            $tenant,
            [
                'role' => $role,
                'password' => $password,
            ]
        );

        $this->resetRequestAuthentication();

        $response = $this->postJson(
            '/api/login',
            [
                'domain' => $domain,
                'email' => $user->email,
                'password' => $password,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'guard',
                'tenant'
            )
            ->assertJsonPath(
                'user.role',
                $role
            )
            ->assertJsonPath(
                'tenant.domain',
                $domain
            );

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $this->resetRequestAuthentication();

        return $this->tenantHeaders(
            $token,
            $domain
        );
    }

    /**
     * Simulate a fresh HTTP authentication lifecycle.
     */
    private function resetRequestAuthentication(): void
    {
        $this->restoreLandlordContext();
        $this->clearAuthenticationState();
    }
}
