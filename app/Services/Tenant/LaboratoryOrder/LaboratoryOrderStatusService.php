<?php

namespace App\Services\Tenant\LaboratoryOrder;

use App\Models\LaboratoryOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LaboratoryOrderStatusService
{
    /**
     * Permitted laboratory-order transitions.
     *
     * @var array<string, array<int, string>>
     */
    private const TRANSITIONS = [
        'pending' => [
            'cancelled',
        ],

        'sample_collected' => [
            'in_progress',
            'cancelled',
        ],

        'in_progress' => [
            'completed',
        ],

        'completed' => [],

        'cancelled' => [],
    ];

    /**
     * Move an order to its next permitted status.
     */
    public function transition(
        string $uuid,
        string $nextStatus
    ): LaboratoryOrder {
        return DB::connection('tenant')
            ->transaction(
                function () use (
                    $uuid,
                    $nextStatus
                ): LaboratoryOrder {
                    $order = LaboratoryOrder::query()
                        ->with([
                            'items.specimens',
                        ])
                        ->where('uuid', $uuid)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $currentStatus = (
                        (string) $order->status
                    );

                    if ($nextStatus === 'sample_collected') {
                        throw ValidationException::withMessages([
                            'status' => [
                                'Record the specimens before marking the order as sample collected.',
                            ],
                        ]);
                    }

                    $this->assertTransitionAllowed(
                        $currentStatus,
                        $nextStatus
                    );

                    if (
                        $currentStatus
                            === 'sample_collected'
                        && $nextStatus
                            === 'in_progress'
                    ) {
                        $this->assertReadyForProcessing(
                            $order
                        );
                    }

                    $order->forceFill(
                        array_merge(
                            [
                                'status' => $nextStatus,
                            ],
                            $this->timestampAttributes(
                                $nextStatus
                            )
                        )
                    )->save();

                    return $order
                        ->fresh()
                        ->load([
                            'patient',
                            'orderedBy',
                            'items',
                            'specimens.collectedBy',
                            'specimens.orderItems',
                        ]);
                },
                3
            );
    }

    /**
     * Ensure the requested transition is permitted.
     */
    private function assertTransitionAllowed(
        string $currentStatus,
        string $nextStatus
    ): void {
        $allowedStatuses = (
            self::TRANSITIONS[$currentStatus]
            ?? []
        );

        if (
            in_array(
                $nextStatus,
                $allowedStatuses,
                true
            )
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => [
                sprintf(
                    'Laboratory order cannot move from %s to %s.',
                    $currentStatus,
                    $nextStatus
                ),
            ],
        ]);
    }

    /**
     * Require received specimen coverage for every active item.
     */
    private function assertReadyForProcessing(
        LaboratoryOrder $order
    ): void {
        $activeItems = $order->items
            ->where(
                'status',
                '!=',
                'cancelled'
            );

        if ($activeItems->isEmpty()) {
            throw ValidationException::withMessages([
                'status' => [
                    'The order has no active items available for processing.',
                ],
            ]);
        }

        $unreadyItems = $activeItems->filter(
            fn ($item): bool => (
                ! $item->specimens->contains(
                    fn ($specimen): bool => (
                        $specimen->status
                            === 'received'
                    )
                )
            )
        );

        if ($unreadyItems->isEmpty()) {
            return;
        }

        throw ValidationException::withMessages([
            'status' => [
                sprintf(
                    '%d active order item(s) do not have a received specimen.',
                    $unreadyItems->count()
                ),
            ],
        ]);
    }

    /**
     * Return timestamps belonging to the new status.
     *
     * @return array<string, mixed>
     */
    private function timestampAttributes(
        string $status
    ): array {
        return match ($status) {
            'completed' => [
                'completed_at' => now(),
            ],

            'cancelled' => [
                'cancelled_at' => now(),
            ],

            default => [],
        };
    }
}
