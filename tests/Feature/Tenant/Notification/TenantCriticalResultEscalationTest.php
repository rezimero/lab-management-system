<?php

namespace Tests\Feature\Tenant\Notification;

use App\Jobs\Tenant\Notification\DeliverNotificationDelivery;
use App\Models\CriticalResultEscalation;
use App\Models\NotificationMessage;
use App\Services\Tenant\Notification\CriticalResultEscalationService;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Tenant\TestResultManagementTestCase;

class TenantCriticalResultEscalationTest extends TestResultManagementTestCase
{
    public function test_approved_critical_result_creates_and_completes_escalation_workflow(): void
    {
        $context = $this->authenticatedTestResultContext(
            'critical-escalation'
        );
        $order = $this->createProcessingOrder(
            $context['tenant'],
            $context['headers']
        );
        $item = $order->items->firstOrFail();

        $this->startOrderItem(
            $context['headers'],
            $item
        )->assertOk();

        $payload = $this->completeResultPayload();
        $payload['results_data'][0]['flag'] = 'critical';
        $resultUuid = (string) $this->createDraftResult(
            $context['headers'],
            $item,
            $payload
        )
            ->assertCreated()
            ->json('data.uuid');

        Queue::fake();

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'.$resultUuid.'/submit'
            )
            ->assertOk();

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/test-results/'.$resultUuid.'/approve'
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $escalationUuid = $this->runInTenant(
            $context['tenant'],
            function () use ($resultUuid): string {
                $escalation = CriticalResultEscalation::query()
                    ->where('test_result_uuid', $resultUuid)
                    ->firstOrFail();

                $this->assertSame('open', $escalation->status);
                $this->assertSame(1, $escalation->critical_count);
                $this->assertTrue(
                    NotificationMessage::query()
                        ->where('category', 'critical_result')
                        ->where('priority', 'critical')
                        ->exists()
                );

                return $escalation->uuid;
            }
        );

        Queue::assertPushed(
            DeliverNotificationDelivery::class
        );

        $this->runInTenant(
            $context['tenant'],
            function () use ($escalationUuid): void {
                CriticalResultEscalation::query()
                    ->where('uuid', $escalationUuid)
                    ->update([
                        'acknowledgement_due_at' => now()->subMinute(),
                    ]);

                $this->assertSame(
                    1,
                    app(CriticalResultEscalationService::class)
                        ->escalateDue()
                );
                $this->assertSame(
                    2,
                    CriticalResultEscalation::query()
                        ->where('uuid', $escalationUuid)
                        ->firstOrFail()
                        ->escalation_level
                );
                $this->assertTrue(
                    NotificationMessage::query()
                        ->where('category', 'critical_result')
                        ->where('idempotency_key', 'like', '%:level:2:%')
                        ->exists()
                );
            }
        );

        $this->withHeaders($context['headers'])
            ->getJson('/api/critical-result-escalations')
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $escalationUuid)
            ->assertJsonPath('data.0.status', 'open')
            ->assertJsonPath('data.0.escalation_level', 2);

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/critical-result-escalations/'
                .$escalationUuid
                .'/acknowledge',
                [
                    'note' => 'Critical result reviewed immediately.',
                ]
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'acknowledged');

        $this->withHeaders($context['headers'])
            ->postJson(
                '/api/critical-result-escalations/'
                .$escalationUuid
                .'/resolve',
                [
                    'note' => 'Responsible clinician contacted and action documented.',
                ]
            )
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->runInTenant(
            $context['tenant'],
            function () use ($escalationUuid): void {
                $escalation = CriticalResultEscalation::query()
                    ->where('uuid', $escalationUuid)
                    ->firstOrFail();

                $this->assertNotNull($escalation->acknowledged_at);
                $this->assertNotNull($escalation->resolved_at);
            }
        );
    }
}
