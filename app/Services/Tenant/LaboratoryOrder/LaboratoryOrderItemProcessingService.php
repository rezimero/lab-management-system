<?php

namespace App\Services\Tenant\LaboratoryOrder;

use App\Models\LaboratoryOrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LaboratoryOrderItemProcessingService
{
    /**
     * Start processing an individual order item.
     */
    public function start(
        string $uuid,
        int $userId
    ): LaboratoryOrderItem {
        return DB::connection('tenant')->transaction(
            function () use (
                $uuid,
                $userId
            ): LaboratoryOrderItem {
                $item = LaboratoryOrderItem::query()
                    ->with([
                        'order',
                        'specimens',
                        'processingStartedBy',
                        'testResult',
                    ])
                    ->where('uuid', $uuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertOrderIsProcessing($item);
                $this->assertItemCanStart($item);
                $this->assertReceivedSpecimenExists($item);

                $item->forceFill([
                    'status' => 'in_progress',
                    'processing_started_by' => $userId,
                    'processing_started_at' => now(),
                    'completed_at' => null,
                ])->save();

                return $item
                    ->fresh()
                    ->load([
                        'order',
                        'specimens',
                        'processingStartedBy',
                        'testResult',
                    ]);
            },
            3
        );
    }

    /**
     * Require an in-progress parent order.
     */
    private function assertOrderIsProcessing(
        LaboratoryOrderItem $item
    ): void {
        if ($item->order?->status === 'in_progress') {
            return;
        }

        throw ValidationException::withMessages([
            'order_item' => [
                'The laboratory order must be in progress before this item can start.',
            ],
        ]);
    }

    /**
     * Require a pending, active order item.
     */
    private function assertItemCanStart(
        LaboratoryOrderItem $item
    ): void {
        if ($item->status === 'cancelled') {
            throw ValidationException::withMessages([
                'order_item' => [
                    'A cancelled laboratory order item cannot be processed.',
                ],
            ]);
        }

        if ($item->status === 'completed') {
            throw ValidationException::withMessages([
                'order_item' => [
                    'A completed laboratory order item cannot be restarted.',
                ],
            ]);
        }

        if ($item->status === 'in_progress') {
            throw ValidationException::withMessages([
                'order_item' => [
                    'This laboratory order item is already in progress.',
                ],
            ]);
        }

        if ($item->status === 'pending') {
            return;
        }

        throw ValidationException::withMessages([
            'order_item' => [
                sprintf(
                    'Laboratory order item cannot start from status %s.',
                    $item->status
                ),
            ],
        ]);
    }

    /**
     * Require at least one received specimen.
     */
    private function assertReceivedSpecimenExists(
        LaboratoryOrderItem $item
    ): void {
        $hasReceivedSpecimen = $item->specimens
            ->contains(
                fn ($specimen): bool => (
                    $specimen->status === 'received'
                )
            );

        if ($hasReceivedSpecimen) {
            return;
        }

        throw ValidationException::withMessages([
            'order_item' => [
                'This laboratory order item does not have a received specimen.',
            ],
        ]);
    }
}
