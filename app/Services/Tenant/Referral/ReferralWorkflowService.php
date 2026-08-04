<?php

namespace App\Services\Tenant\Referral;

use App\Models\LaboratoryReferral;
use App\Models\LaboratoryReferralEvent;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReferralWorkflowService
{
    private const RELATIONS = [
        'orderItem.order.patient',
        'orderItem.order.collectionBranch',
        'orderItem.order.processingBranch',
        'orderItem.order.priceList',
        'dispatchBranch',
        'referralLaboratory',
        'referralLaboratoryTest.labTest',
        'specimens',
        'events.actor',
    ];

    public function __construct(
        protected ReferralBranchAccessService $branchAccess,
        protected ReferralActorSnapshotFactory $actorSnapshots
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function dispatch(
        string $uuid,
        array $data,
        TenantUser $actor
    ): LaboratoryReferral {
        return $this->mutate(
            $uuid,
            $actor,
            function (
                LaboratoryReferral $referral
            ) use ($data, $actor): void {
                $this->assertStatus(
                    $referral,
                    [LaboratoryReferral::STATUS_DRAFT],
                    'dispatch'
                );

                $now = now();
                $turnaroundHours = data_get(
                    $referral->test_snapshot,
                    'turnaround_hours'
                );

                $referral->forceFill([
                    'status' => LaboratoryReferral::STATUS_DISPATCHED,
                    'quoted_cost' => $data['quoted_cost']
                        ?? $referral->quoted_cost,
                    'external_reference' => $this->nullableText(
                        $data['external_reference']
                            ?? $referral->external_reference
                    ),
                    'dispatch_notes' => $this->nullableText(
                        $data['dispatch_notes']
                            ?? $referral->dispatch_notes
                    ),
                    'dispatched_by' => $actor->getKey(),
                    'dispatched_at' => $now,
                    'turnaround_due_at' => $turnaroundHours === null
                        ? null
                        : $now->copy()->addHours(
                            (int) $turnaroundHours
                        ),
                ])->save();

                $this->recordEvent(
                    $referral,
                    'dispatched',
                    LaboratoryReferral::STATUS_DRAFT,
                    LaboratoryReferral::STATUS_DISPATCHED,
                    $actor,
                    [
                        'external_reference' => (
                            $referral->external_reference
                        ),
                        'dispatch_notes' => $referral->dispatch_notes,
                        'quoted_cost' => $referral->quoted_cost,
                        'currency' => $referral->currency,
                        'turnaround_due_at' => (
                            $referral->turnaround_due_at
                                ?->toISOString()
                        ),
                    ],
                    $now
                );
            }
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function acknowledge(
        string $uuid,
        array $data,
        TenantUser $actor
    ): LaboratoryReferral {
        return $this->mutate(
            $uuid,
            $actor,
            function (
                LaboratoryReferral $referral
            ) use ($data, $actor): void {
                $this->assertStatus(
                    $referral,
                    [LaboratoryReferral::STATUS_DISPATCHED],
                    'acknowledge'
                );

                $now = now();

                $referral->forceFill([
                    'status' => LaboratoryReferral::STATUS_ACKNOWLEDGED,
                    'external_reference' => $this->nullableText(
                        $data['external_reference']
                            ?? $referral->external_reference
                    ),
                    'acknowledged_by' => $actor->getKey(),
                    'acknowledged_at' => $now,
                ])->save();

                $this->recordEvent(
                    $referral,
                    'acknowledged',
                    LaboratoryReferral::STATUS_DISPATCHED,
                    LaboratoryReferral::STATUS_ACKNOWLEDGED,
                    $actor,
                    [
                        'external_reference' => (
                            $referral->external_reference
                        ),
                    ],
                    $now
                );
            }
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function reject(
        string $uuid,
        array $data,
        TenantUser $actor
    ): LaboratoryReferral {
        return $this->mutate(
            $uuid,
            $actor,
            function (
                LaboratoryReferral $referral
            ) use ($data, $actor): void {
                $from = (string) $referral->status;

                $this->assertStatus(
                    $referral,
                    [
                        LaboratoryReferral::STATUS_DISPATCHED,
                        LaboratoryReferral::STATUS_ACKNOWLEDGED,
                    ],
                    'reject'
                );

                $now = now();
                $reason = trim((string) $data['reason']);

                $referral->forceFill([
                    'status' => LaboratoryReferral::STATUS_REJECTED,
                    'open_order_item_key' => null,
                    'rejected_by' => $actor->getKey(),
                    'rejected_at' => $now,
                    'rejection_reason' => $reason,
                ])->save();

                $this->recordEvent(
                    $referral,
                    'rejected',
                    $from,
                    LaboratoryReferral::STATUS_REJECTED,
                    $actor,
                    ['reason' => $reason],
                    $now
                );
            }
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function cancel(
        string $uuid,
        array $data,
        TenantUser $actor
    ): LaboratoryReferral {
        return $this->mutate(
            $uuid,
            $actor,
            function (
                LaboratoryReferral $referral
            ) use ($data, $actor): void {
                $from = (string) $referral->status;

                $this->assertStatus(
                    $referral,
                    [
                        LaboratoryReferral::STATUS_DRAFT,
                        LaboratoryReferral::STATUS_DISPATCHED,
                        LaboratoryReferral::STATUS_ACKNOWLEDGED,
                    ],
                    'cancel'
                );

                $now = now();
                $reason = trim((string) $data['reason']);

                $referral->forceFill([
                    'status' => LaboratoryReferral::STATUS_CANCELLED,
                    'open_order_item_key' => null,
                    'cancelled_by' => $actor->getKey(),
                    'cancelled_at' => $now,
                    'cancellation_reason' => $reason,
                ])->save();

                $this->recordEvent(
                    $referral,
                    'cancelled',
                    $from,
                    LaboratoryReferral::STATUS_CANCELLED,
                    $actor,
                    ['reason' => $reason],
                    $now
                );
            }
        );
    }

    /**
     * @param  callable(LaboratoryReferral): void  $operation
     */
    private function mutate(
        string $uuid,
        TenantUser $actor,
        callable $operation
    ): LaboratoryReferral {
        return DB::connection('tenant')->transaction(
            function () use (
                $uuid,
                $actor,
                $operation
            ): LaboratoryReferral {
                $referral = LaboratoryReferral::query()
                    ->with([
                        'orderItem.order.processingBranch',
                    ])
                    ->where('uuid', $uuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                $processingBranch = $referral
                    ->orderItem
                    ?->order
                    ?->processingBranch;

                if ($processingBranch === null) {
                    throw ValidationException::withMessages([
                        'referral' => [
                            'The referral processing branch is unavailable.',
                        ],
                    ]);
                }

                $this->branchAccess->authorizeMutation(
                    $actor,
                    $processingBranch
                );

                $operation($referral);

                return $referral->fresh(self::RELATIONS);
            },
            3
        );
    }

    /**
     * @param  array<int, string>  $allowed
     */
    private function assertStatus(
        LaboratoryReferral $referral,
        array $allowed,
        string $action
    ): void {
        if (in_array($referral->status, $allowed, true)) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => [
                sprintf(
                    'A %s referral cannot perform the %s action.',
                    $referral->status,
                    $action
                ),
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    private function recordEvent(
        LaboratoryReferral $referral,
        string $type,
        ?string $from,
        string $to,
        TenantUser $actor,
        ?array $payload,
        mixed $occurredAt
    ): void {
        LaboratoryReferralEvent::query()->create([
            'laboratory_referral_id' => $referral->getKey(),
            'event_type' => $type,
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor->getKey(),
            'actor_snapshot' => $this->actorSnapshots->make($actor),
            'payload' => $payload,
            'occurred_at' => $occurredAt,
            'created_at' => now(),
        ]);
    }

    private function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
