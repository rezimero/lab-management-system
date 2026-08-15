<?php

namespace Tests\Feature\Tenant\Notification;

use App\Jobs\Tenant\Notification\DeliverNotificationDelivery;
use App\Models\ClinicianPortalAccount;
use App\Models\NotificationDelivery;
use App\Models\NotificationMessage;
use App\Models\PatientPortalAccount;
use App\Models\ReferringClinician;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Tenant\LaboratoryReportTestCase;

class TenantReportReadyNotificationTest extends LaboratoryReportTestCase
{
    public function test_report_issuance_records_patient_and_clinician_notices(): void
    {
        $context = $this->authenticatedReportContext(
            'notification-report-ready'
        );
        $order = $this->createReportReadyOrder(
            $context['tenant'],
            $context['headers']
        );

        $this->runInTenant(
            $context['tenant'],
            function () use ($order): void {
                PatientPortalAccount::query()->create([
                    'patient_id' => $order->patient_id,
                    'email' => 'notice-patient@example.test',
                    'password' => 'PortalPassword123!',
                    'email_verified_at' => now(),
                    'activated_at' => now(),
                    'is_active' => true,
                ]);

                $clinician = ReferringClinician::query()->create([
                    'name' => 'Dr Notification Clinician',
                    'email' => 'notice-clinician@example.test',
                    'is_active' => true,
                ]);

                ClinicianPortalAccount::query()->create([
                    'referring_clinician_id' => $clinician->getKey(),
                    'email' => 'notice-clinician@example.test',
                    'password' => 'PortalPassword123!',
                    'email_verified_at' => now(),
                    'activated_at' => now(),
                    'is_active' => true,
                ]);

                $order->update([
                    'referring_clinician_id' => $clinician->getKey(),
                    'referring_clinician' => $clinician->name,
                ]);
            }
        );

        Queue::fake();

        $response = $this->generateReport(
            $context['headers'],
            $order
        )->assertCreated();
        $reportUuid = (string) $response->json('data.uuid');

        $this->runInTenant(
            $context['tenant'],
            function () use ($reportUuid): void {
                $messages = NotificationMessage::query()
                    ->where('category', 'report_ready')
                    ->orderBy('id')
                    ->get();
                $deliveries = NotificationDelivery::query()
                    ->where('status', NotificationDelivery::STATUS_PENDING)
                    ->orderBy('id')
                    ->get();

                $this->assertCount(2, $messages);
                $this->assertCount(2, $deliveries);
                $this->assertSame(
                    [
                        NotificationMessage::RECIPIENT_PATIENT_PORTAL,
                        NotificationMessage::RECIPIENT_CLINICIAN_PORTAL,
                    ],
                    $messages->pluck('recipient_type')->all()
                );

                foreach ($deliveries as $delivery) {
                    $payload = $delivery->payload;

                    $this->assertIsArray($payload);
                    $this->assertStringContainsString(
                        $reportUuid,
                        (string) $payload['action_url']
                    );
                    $this->assertStringNotContainsString(
                        'Result Patient',
                        json_encode($payload, JSON_THROW_ON_ERROR)
                    );
                    $this->assertStringNotContainsString(
                        'RPT-',
                        json_encode($payload, JSON_THROW_ON_ERROR)
                    );
                }
            }
        );

        Queue::assertPushed(
            DeliverNotificationDelivery::class,
            2
        );
    }
}
