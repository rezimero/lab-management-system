<?php

namespace Tests\Feature\Tenant\Notification;

use App\Models\NotificationMessage;
use App\Models\Tenant;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Tenant\PortalAuthenticationTestCase;

class TenantNotificationIsolationTest extends PortalAuthenticationTestCase
{
    public function test_notification_tables_exist_only_in_tenant_database(): void
    {
        $tenant = $this->registerTestTenant('notification-schema');
        $tables = [
            'notification_messages',
            'notification_deliveries',
            'notification_preferences',
            'critical_result_escalations',
            'portal_password_resets',
        ];

        $this->runInPortalTenant(
            $tenant['tenant'],
            function () use ($tables): void {
                foreach ($tables as $table) {
                    $this->assertTrue(
                        Schema::connection('tenant')->hasTable($table),
                        'Missing tenant Notifications table: '.$table
                    );
                }
            }
        );

        foreach ($tables as $table) {
            $this->assertFalse(
                Schema::connection('landlord')->hasTable($table),
                'Notifications table leaked into landlord: '.$table
            );
        }
    }

    public function test_same_notification_uuid_remains_isolated_between_tenants(): void
    {
        $tenantA = $this->registerTestTenant('notification-isolation-a');
        $tenantB = $this->registerTestTenant('notification-isolation-b');
        $patientA = $this->createPatientPortalIdentity($tenantA['tenant']);
        $patientB = $this->createPatientPortalIdentity($tenantB['tenant']);
        $sharedUuid = '11111111-2222-4333-8444-555555555555';

        $this->createPortalMessage(
            $tenantA['tenant'],
            $patientA['account']->getKey(),
            $patientA['account']->uuid,
            $sharedUuid,
            'Tenant A notice'
        );
        $this->createPortalMessage(
            $tenantB['tenant'],
            $patientB['account']->getKey(),
            $patientB['account']->uuid,
            $sharedUuid,
            'Tenant B notice'
        );

        $tokenA = $this->patientLoginToken($tenantA, $patientA);
        $tokenB = $this->patientLoginToken($tenantB, $patientB);

        $this->withHeaders($this->portalHeaders(
            $tokenA,
            $tenantA['domain']
        ))
            ->getJson('/api/portal/patient/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $sharedUuid)
            ->assertJsonPath('data.0.title', 'Tenant A notice');

        $this->withHeaders($this->portalHeaders(
            $tokenB,
            $tenantB['domain']
        ))
            ->getJson('/api/portal/patient/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $sharedUuid)
            ->assertJsonPath('data.0.title', 'Tenant B notice');
    }

    public function test_patient_cannot_mark_another_patient_notification_read(): void
    {
        $tenant = $this->registerTestTenant('notification-recipient-scope');
        $patientA = $this->createPatientPortalIdentity($tenant['tenant']);
        $patientB = $this->createPatientPortalIdentity($tenant['tenant']);
        $messageUuid = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeeeee';

        $this->createPortalMessage(
            $tenant['tenant'],
            $patientB['account']->getKey(),
            $patientB['account']->uuid,
            $messageUuid,
            'Private patient B notice'
        );

        $tokenA = $this->patientLoginToken($tenant, $patientA);

        $this->withHeaders($this->portalHeaders(
            $tokenA,
            $tenant['domain']
        ))
            ->patchJson(
                '/api/portal/patient/notifications/'
                .$messageUuid.'/read'
            )
            ->assertNotFound();

        $this->runInPortalTenant(
            $tenant['tenant'],
            function () use ($messageUuid): void {
                $this->assertNull(
                    NotificationMessage::query()
                        ->where('uuid', $messageUuid)
                        ->firstOrFail()
                        ->read_at
                );
            }
        );
    }

    private function createPortalMessage(
        Tenant $tenant,
        int $accountId,
        string $accountUuid,
        string $messageUuid,
        string $title
    ): void {
        $this->runInPortalTenant(
            $tenant,
            static function () use (
                $accountId,
                $accountUuid,
                $messageUuid,
                $title
            ): void {
                NotificationMessage::query()->create([
                    'uuid' => $messageUuid,
                    'idempotency_key' => 'isolation:'.$messageUuid,
                    'recipient_type' => (
                        NotificationMessage::RECIPIENT_PATIENT_PORTAL
                    ),
                    'recipient_id' => $accountId,
                    'recipient_uuid' => $accountUuid,
                    'category' => 'report_ready',
                    'event_key' => 'isolation_test',
                    'priority' => 'normal',
                    'title' => $title,
                    'body' => 'A secure notice is available.',
                ]);
            }
        );
    }

    /**
     * @param  array<string, mixed>  $tenant
     * @param  array<string, mixed>  $identity
     */
    private function patientLoginToken(
        array $tenant,
        array $identity
    ): string {
        return (string) $this->postJson(
            '/api/portal/patient/login',
            [
                'domain' => $tenant['domain'],
                'email' => $identity['email'],
                'password' => $identity['password'],
            ]
        )
            ->assertOk()
            ->json('token');
    }
}
