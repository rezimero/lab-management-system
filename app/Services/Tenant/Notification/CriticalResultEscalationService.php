<?php

namespace App\Services\Tenant\Notification;

use App\Models\CriticalResultEscalation;
use App\Models\Tenant\User as TenantUser;
use App\Models\TestResult;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CriticalResultEscalationService
{
    public function __construct(
        private readonly NotificationPublisher $publisher
    ) {}

    public function recordApproved(
        string $testResultUuid,
        ?int $knownCriticalCount = null,
        ?string $sourceKey = null
    ): ?CriticalResultEscalation {
        return DB::connection('tenant')->transaction(
            fn (): ?CriticalResultEscalation => $this->recordApprovedLocked(
                $testResultUuid,
                $knownCriticalCount,
                $sourceKey
            ),
            3
        );
    }

    private function recordApprovedLocked(
        string $testResultUuid,
        ?int $knownCriticalCount,
        ?string $sourceKey
    ): ?CriticalResultEscalation {
        $result = TestResult::query()
            ->with('orderItem.order')
            ->where('uuid', $testResultUuid)
            ->first();

        if (
            ! $result instanceof TestResult
            || $result->status !== TestResult::STATUS_APPROVED
        ) {
            return null;
        }

        $criticalCount = $knownCriticalCount ?? (
            collect($result->results_data ?? [])
                ->filter(
                    static fn (mixed $row): bool => (
                        is_array($row)
                        && strtolower((string) ($row['flag'] ?? ''))
                            === 'critical'
                    )
                )
                ->count()
        );

        $order = $result->orderItem?->order;

        if ($criticalCount === 0 || $order === null) {
            return null;
        }

        $escalation = CriticalResultEscalation::query()
            ->firstOrCreate(
                [
                    'source_key' => $sourceKey
                        ?? 'test-result:'.$result->uuid.':approved',
                ],
                [
                    'test_result_id' => $result->getKey(),
                    'test_result_uuid' => $result->uuid,
                    'order_uuid' => $order->uuid,
                    'critical_count' => $criticalCount,
                    'status' => CriticalResultEscalation::STATUS_OPEN,
                    'escalation_level' => 1,
                    'detected_at' => now(),
                    'acknowledgement_due_at' => now()->addMinutes(
                        (int) config(
                            'rezhel_notifications.critical_acknowledgement_minutes',
                            15
                        )
                    ),
                ]
            );

        $this->notifyResponders($escalation);

        return $escalation;
    }

    public function acknowledge(
        string $uuid,
        TenantUser $actor,
        string $note
    ): CriticalResultEscalation {
        return DB::connection('tenant')->transaction(
            function () use (
                $uuid,
                $actor,
                $note
            ): CriticalResultEscalation {
                $escalation = CriticalResultEscalation::query()
                    ->where('uuid', $uuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($escalation->status !== CriticalResultEscalation::STATUS_OPEN) {
                    throw ValidationException::withMessages([
                        'escalation' => [
                            'Only an open critical result can be acknowledged.',
                        ],
                    ]);
                }

                $escalation->forceFill([
                    'status' => CriticalResultEscalation::STATUS_ACKNOWLEDGED,
                    'acknowledged_by' => $actor->getKey(),
                    'acknowledged_at' => now(),
                    'acknowledgement_note' => trim($note),
                ])->save();

                return $escalation->refresh()->load(
                    'testResult.orderItem.order.patient'
                );
            },
            3
        );
    }

    public function resolve(
        string $uuid,
        TenantUser $actor,
        string $note
    ): CriticalResultEscalation {
        return DB::connection('tenant')->transaction(
            function () use (
                $uuid,
                $actor,
                $note
            ): CriticalResultEscalation {
                $escalation = CriticalResultEscalation::query()
                    ->where('uuid', $uuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $escalation->status
                    !== CriticalResultEscalation::STATUS_ACKNOWLEDGED
                ) {
                    throw ValidationException::withMessages([
                        'escalation' => [
                            'A critical result must be acknowledged before resolution.',
                        ],
                    ]);
                }

                $escalation->forceFill([
                    'status' => CriticalResultEscalation::STATUS_RESOLVED,
                    'resolved_by' => $actor->getKey(),
                    'resolved_at' => now(),
                    'resolution_note' => trim($note),
                ])->save();

                return $escalation->refresh()->load(
                    'testResult.orderItem.order.patient'
                );
            },
            3
        );
    }

    public function escalateDue(): int
    {
        $count = 0;

        CriticalResultEscalation::query()
            ->where('status', CriticalResultEscalation::STATUS_OPEN)
            ->where('acknowledgement_due_at', '<=', now())
            ->orderBy('id')
            ->chunkById(
                50,
                function (Collection $escalations) use (&$count): void {
                    foreach ($escalations as $candidate) {
                        $escalation = DB::connection('tenant')->transaction(
                            function () use (
                                $candidate
                            ): ?CriticalResultEscalation {
                                $locked = CriticalResultEscalation::query()
                                    ->whereKey($candidate->getKey())
                                    ->lockForUpdate()
                                    ->first();

                                if (
                                    ! $locked instanceof CriticalResultEscalation
                                    || $locked->status
                                        !== CriticalResultEscalation::STATUS_OPEN
                                    || $locked->acknowledgement_due_at->isFuture()
                                ) {
                                    return null;
                                }

                                $locked->forceFill([
                                    'escalation_level' => (
                                        $locked->escalation_level + 1
                                    ),
                                    'last_escalated_at' => now(),
                                    'acknowledgement_due_at' => now()->addMinutes(
                                        (int) config(
                                            'rezhel_notifications.critical_repeat_minutes',
                                            15
                                        )
                                    ),
                                ])->save();

                                $this->notifyResponders($locked);

                                return $locked->refresh();
                            },
                            3
                        );

                        if ($escalation instanceof CriticalResultEscalation) {
                            $count++;
                        }
                    }
                }
            );

        return $count;
    }

    private function notifyResponders(
        CriticalResultEscalation $escalation
    ): void {
        $this->responders()->each(
            function (TenantUser $user) use (
                $escalation
            ): void {
                $level = (int) $escalation->escalation_level;
                $title = $level > 1
                    ? 'Critical result remains unacknowledged'
                    : 'Critical result requires acknowledgement';

                $this->publisher->publish(
                    recipient: NotificationRecipient::tenantUser($user),
                    idempotencyKey: 'critical-result:'
                        .$escalation->uuid
                        .':level:'.$level
                        .':user:'.$user->getKey(),
                    category: 'critical_result',
                    eventKey: $level > 1
                        ? 'critical_result_escalated'
                        : 'critical_result_detected',
                    title: $title,
                    body: (
                        'An approved critical result requires prompt secure '
                        .'review and acknowledgement.'
                    ),
                    actionUrl: '/notifications?critical='
                        .$escalation->uuid,
                    priority: 'critical',
                    data: [
                        'critical_escalation_uuid' => $escalation->uuid,
                        'test_result_uuid' => $escalation->test_result_uuid,
                        'escalation_level' => $level,
                    ],
                    mandatory: true,
                    email: [
                        'subject' => 'Critical laboratory result requires attention',
                        'title' => $title,
                        'body' => (
                            'A critical result requires prompt attention. Sign '
                            .'in to the laboratory system; no patient or result '
                            .'details are included in this email.'
                        ),
                        'action_label' => 'Open secure workspace',
                        'action_url' => (string) config(
                            'rezhel_notifications.portal_frontend_url'
                        ).'/notifications?critical='.$escalation->uuid,
                    ],
                );
            }
        );
    }

    /**
     * @return Collection<int, TenantUser>
     */
    private function responders(): Collection
    {
        return TenantUser::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->filter(
                static fn (TenantUser $user): bool => (
                    $user->hasPermission(
                        TenantUserPermissionCatalog::ACKNOWLEDGE_CRITICAL_RESULTS
                    )
                )
            )
            ->values();
    }
}
