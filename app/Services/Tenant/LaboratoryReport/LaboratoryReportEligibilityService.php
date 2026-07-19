<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Models\LaboratoryOrder;
use App\Models\LaboratoryOrderItem;
use App\Models\TestResult;
use Illuminate\Validation\ValidationException;

class LaboratoryReportEligibilityService
{
    /**
     * Ensure an order is ready for report issuance.
     */
    public function assertEligible(
        LaboratoryOrder $order
    ): void {
        if ($order->status !== 'completed') {
            $this->fail(
                'A laboratory report can only be generated '
                .'for a completed order.'
            );
        }

        if ($order->patient === null) {
            $this->fail(
                'The laboratory order does not have a patient.'
            );
        }

        $activeItems = $order->items
            ->reject(
                static fn (
                    LaboratoryOrderItem $item
                ): bool => (
                    $item->status === 'cancelled'
                )
            );

        if ($activeItems->isEmpty()) {
            $this->fail(
                'The laboratory order has no active test items.'
            );
        }

        foreach ($activeItems as $item) {
            if ($item->status !== 'completed') {
                $this->fail(
                    'Every active laboratory order item must '
                    .'be completed before report generation.'
                );
            }

            if ($item->testResult === null) {
                $this->fail(
                    'Every active laboratory order item must '
                    .'have a test result.'
                );
            }

            if (
                $item->testResult->status
                !== TestResult::STATUS_APPROVED
            ) {
                $this->fail(
                    'Every active laboratory order item must '
                    .'have an approved test result.'
                );
            }
        }
    }

    /**
     * Throw a report-generation validation error.
     */
    private function fail(
        string $message
    ): never {
        throw ValidationException::withMessages([
            'laboratory_order' => $message,
        ]);
    }
}
