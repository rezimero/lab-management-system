<?php

namespace Tests\Feature\Tenant\Notification;

use App\Jobs\Tenant\Notification\DeliverNotificationDelivery;
use App\Models\NotificationDelivery;
use App\Models\PortalAccessToken;
use App\Models\PortalAuditEvent;
use App\Models\PortalPasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Tenant\PortalAuthenticationTestCase;

class TenantPortalPasswordResetNotificationTest extends PortalAuthenticationTestCase
{
    public function test_administrator_can_send_one_time_patient_password_reset(): void
    {
        $registration = $this->registerTestTenant(
            'notification-reset'
        );
        $identity = $this->createPatientPortalIdentity(
            $registration['tenant']
        );
        $headers = $this->tenantHeaders(
            $registration['token'],
            $registration['domain']
        );

        $this->runInPortalTenant(
            $registration['tenant'],
            function () use ($identity): void {
                PortalAccessToken::query()->create([
                    'account_type' => PortalAccessToken::ACCOUNT_PATIENT,
                    'account_id' => $identity['account']->getKey(),
                    'token_hash' => hash('sha256', 'active-session-token'),
                    'name' => 'portal_session',
                    'abilities' => ['patient:read'],
                    'expires_at' => now()->addHour(),
                ]);
            }
        );

        Queue::fake();

        $this->withHeaders($headers)
            ->postJson(
                '/api/portal-access/patient-accounts/'
                .$identity['account']->uuid
                .'/password-reset'
            )
            ->assertAccepted()
            ->assertJsonPath(
                'message',
                'A secure patient password reset request was recorded for delivery.'
            );

        $plainTextToken = $this->runInPortalTenant(
            $registration['tenant'],
            function () use ($identity): string {
                $reset = PortalPasswordReset::query()
                    ->where('account_type', 'patient')
                    ->where(
                        'account_id',
                        $identity['account']->getKey()
                    )
                    ->firstOrFail();
                $delivery = NotificationDelivery::query()
                    ->where(
                        'idempotency_key',
                        'portal-password-reset:'
                        .$reset->uuid.':email'
                    )
                    ->firstOrFail();
                $payload = $delivery->payload;

                $this->assertSame('pending', $delivery->status);
                $this->assertIsArray($payload);

                $fragment = parse_url(
                    (string) $payload['action_url'],
                    PHP_URL_FRAGMENT
                );
                parse_str((string) $fragment, $values);
                $token = (string) ($values['token'] ?? '');

                $this->assertNotSame('', $token);
                $this->assertSame(
                    hash('sha256', $token),
                    $reset->getRawOriginal('token_hash')
                );

                return $token;
            }
        );

        Queue::assertPushed(
            DeliverNotificationDelivery::class,
            1
        );

        $portalHeaders = [
            'Accept' => 'application/json',
            'X-Tenant-Domain' => $registration['domain'],
        ];

        $this->withHeaders($portalHeaders)
            ->postJson('/api/portal/password-reset/inspect', [
                'token' => $plainTextToken,
            ])
            ->assertOk()
            ->assertJsonPath('account_type', 'patient');

        $newPassword = 'ChangedPortalPassword123!';

        $this->withHeaders($portalHeaders)
            ->postJson('/api/portal/password-reset/complete', [
                'token' => $plainTextToken,
                'password' => $newPassword,
                'password_confirmation' => $newPassword,
            ])
            ->assertOk()
            ->assertJsonPath('account_type', 'patient');

        $this->withHeaders($portalHeaders)
            ->postJson('/api/portal/password-reset/inspect', [
                'token' => $plainTextToken,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('token');

        $this->runInPortalTenant(
            $registration['tenant'],
            function () use (
                $identity,
                $newPassword
            ): void {
                $account = $identity['account']->fresh();

                $this->assertTrue(Hash::check(
                    $newPassword,
                    $account->password
                ));
                $this->assertFalse(Hash::check(
                    $identity['password'],
                    $account->password
                ));
                $this->assertTrue(
                    PortalAccessToken::query()
                        ->where(
                            'account_id',
                            $account->getKey()
                        )
                        ->whereNotNull('revoked_at')
                        ->exists()
                );
                $this->assertTrue(
                    PortalAuditEvent::query()
                        ->where(
                            'event',
                            'portal_password_reset_completed'
                        )
                        ->where('subject_uuid', $account->uuid)
                        ->exists()
                );
            }
        );
    }

    public function test_self_service_reset_response_does_not_reveal_account_existence(): void
    {
        $registration = $this->registerTestTenant(
            'notification-reset-generic'
        );
        Queue::fake();
        $headers = [
            'Accept' => 'application/json',
            'X-Tenant-Domain' => $registration['domain'],
        ];

        $this->withHeaders($headers)
            ->postJson('/api/portal/password-reset/request', [
                'account_type' => 'patient',
                'email' => 'unknown-account@example.test',
            ])
            ->assertAccepted()
            ->assertJsonPath(
                'message',
                'If an eligible portal account exists, a one-time password reset link will be sent.'
            );

        $this->runInPortalTenant(
            $registration['tenant'],
            function (): void {
                $this->assertSame(
                    0,
                    PortalPasswordReset::query()->count()
                );
                $this->assertSame(
                    0,
                    NotificationDelivery::query()->count()
                );
            }
        );
    }
}
