<?php

namespace App\Services\Tenant\LaboratoryOrder;

use App\Models\LaboratoryOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LaboratoryOrderStatusService
{
    /**
     * Permitted order-level status transitions.
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
                        ->where('uuid', $uuid)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $currentStatus = (
                        (string) $order->status
                    );

                    $this->assertTransitionAllowed(
                        $currentStatus,
                        $nextStatus
                    );

                    $order->forceFill(
                        array_merge(
                            [
                                'status' => $nextStatus,
                            ],
                            $this->timestampAttributes(
                                $nextStatus
                            )
                        )
                    );

                    $order->save();

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
        if ($nextStatus === 'sample_collected') {
            throw ValidationException::withMessages([
                'status' => [
                    'Record the specimens before marking the order as sample collected.',
                ],
            ]);
        }

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
     * Return the timestamp belonging to the new status.
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
