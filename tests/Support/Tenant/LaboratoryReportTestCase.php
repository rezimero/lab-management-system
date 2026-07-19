<?php

namespace Tests\Support\Tenant;

use App\Models\LaboratoryOrder;
use App\Models\LaboratoryReport;
use App\Models\Tenant;
use App\Models\TenantProfile;
use Illuminate\Testing\TestResponse;

abstract class LaboratoryReportTestCase extends
    TestResultManagementTestCase
{
    /**
     * Register and authenticate a report tenant.
     *
     * @return array<string, mixed>
     */
    protected function authenticatedReportContext(
        string $prefix = 'laboratory-report'
    ): array {
        return $this->authenticatedTestResultContext(
            $prefix
        );
    }

    /**
     * Configure report branding.
     *
     * @param array<string, mixed> $overrides
     */
    protected function configureReportProfile(
        Tenant $tenant,
        array $overrides = []
    ): TenantProfile {
        return TenantProfile::query()
            ->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                ],
                array_merge(
                    [
                        'lab_name' => (
                            'Clinical Diagnostics Laboratory'
                        ),
                        'tagline' => (
                            'Accurate results, trusted care'
                        ),
                        'email' => (
                            'reports@example.test'
                        ),
                        'phone' => '+2348000000000',
                        'address' => '12 Medical Avenue',
                        'city' => 'Abuja',
                        'state' => 'FCT',
                        'country' => 'Nigeria',
                        'license_number' => 'LAB-1001',
                        'report_footer' => (
                            'This report must be interpreted '
                            .'by a qualified clinician.'
                        ),
                        'director_name' => (
                            'Dr Report Director'
                        ),
                    ],
                    $overrides
                )
            );
    }

    /**
     * Create a completed order with approved results.
     *
     * @param array<string, string> $headers
     */
    protected function createReportReadyOrder(
        Tenant $tenant,
        array $headers,
        int $itemCount = 2
    ): LaboratoryOrder {
        $order = $this->createProcessingOrder(
            $tenant,
            $headers,
            $itemCount
        );

        foreach ($order->items as $item) {
            $this->startOrderItem(
                $headers,
                $item
            )->assertOk();

            $draft = $this
                ->createDraftResult(
                    $headers,
                    $item
                )
                ->assertCreated();

            $resultUuid = $draft->json(
                'data.uuid'
            );

            $this->assertIsString(
                $resultUuid
            );

            $this
                ->withHeaders($headers)
                ->postJson(
                    '/api/test-results/'
                    .$resultUuid
                    .'/submit'
                )
                ->assertOk();

            $this
                ->withHeaders($headers)
                ->postJson(
                    '/api/test-results/'
                    .$resultUuid
                    .'/approve'
                )
                ->assertOk();
        }

        $this
            ->withHeaders($headers)
            ->patchJson(
                '/api/laboratory-orders/'
                .$order->uuid
                .'/status',
                [
                    'status' => 'completed',
                ]
            )
            ->assertOk()
            ->assertJsonPath(
                'data.status',
                'completed'
            );

        return $this->storedProcessingOrder(
            $tenant,
            $order->uuid
        );
    }

    /**
     * Generate a report through the API.
     *
     * @param array<string, string> $headers
     */
    protected function generateReport(
        array $headers,
        LaboratoryOrder $order
    ): TestResponse {
        return $this
            ->withHeaders($headers)
            ->postJson(
                '/api/laboratory-orders/'
                .$order->uuid
                .'/report'
            );
    }

    /**
     * Retrieve a stored report from its tenant DB.
     */
    protected function storedReport(
        Tenant $tenant,
        string $uuid
    ): LaboratoryReport {
        return $this->runInTenant(
            $tenant,
            static fn (): LaboratoryReport => (
                LaboratoryReport::query()
                    ->with('generatedBy')
                    ->where('uuid', $uuid)
                    ->firstOrFail()
            )
        );
    }

    /**
     * Authenticate a tenant user with a report role.
     *
     * @return array<string, string>
     */
    protected function headersForReportRole(
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

        $this->resetReportAuthentication();

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
            );

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        $this->resetReportAuthentication();

        return $this->tenantHeaders(
            $token,
            $domain
        );
    }

    /**
     * Simulate a fresh HTTP authentication lifecycle.
     */
    protected function resetReportAuthentication(): void
    {
        $this->restoreLandlordContext();
        $this->clearAuthenticationState();
    }
}
