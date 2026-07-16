<?php

namespace App\Services\Tenant\Specimen;

use App\Models\Specimen;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SpecimenStatusService
{
    /**
     * Permitted specimen status transitions.
     *
     * @var array<string, array<int, string>>
     */
    private const TRANSITIONS = [
        'collected' => [
            'received',
            'rejected',
        ],

        'received' => [
            'rejected',
        ],

        'rejected' => [],
    ];

    /**
     * Receive or reject a specimen.
     *
     * @param array<string, mixed> $payload
     */
    public function transition(
        string $uuid,
        array $payload
    ): Specimen {
        return DB::connection('tenant')
            ->transaction(
                function () use (
                    $uuid,
                    $payload
                ): Specimen {
                    $specimen = Specimen::query()
                        ->where('uuid', $uuid)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $currentStatus = (
                        (string) $specimen->status
                    );

                    $nextStatus = (
                        (string) $payload['status']
                    );

                    $this->assertTransitionAllowed(
                        $currentStatus,
                        $nextStatus
                    );

                    $specimen->forceFill(
                        $this->statusAttributes(
                            $nextStatus,
                            $payload
                        )
                    )->save();

                    return $specimen
                        ->fresh()
                        ->load([
                            'order.patient',
                            'collectedBy',
                            'orderItems',
                            'replaces',
                            'replacement',
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
                    'Specimen cannot move from %s to %s.',
                    $currentStatus,
                    $nextStatus
                ),
            ],
        ]);
    }

    /**
     * Build the attributes belonging to the new status.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function statusAttributes(
        string $status,
        array $payload
    ): array {
        if ($status === 'received') {
            return [
                'status' => 'received',
                'received_at' => now(),
                'rejected_at' => null,
                'rejection_reason' => null,
            ];
        }

        return [
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejection_reason' => (
                $this->rejectionReason($payload)
            ),
        ];
    }

    /**
     * Return a valid rejection reason.
     *
     * @param array<string, mixed> $payload
     */
    private function rejectionReason(
        array $payload
    ): string {
        $reason = trim(
            (string) (
                $payload['rejection_reason']
                ?? ''
            )
        );

        if ($reason !== '') {
            return $reason;
        }

        throw ValidationException::withMessages([
            'rejection_reason' => [
                'A rejection reason is required when rejecting a specimen.',
            ],
        ]);
    }
}
