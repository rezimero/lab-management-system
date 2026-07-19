<?php

namespace Tests\Feature\Tenant\LaboratoryReport;

use App\Models\LaboratoryOrderItem;
use App\Models\TestResult;
use Tests\Support\Tenant\LaboratoryReportTestCase;

class TenantLaboratoryReportValidationTest extends
    LaboratoryReportTestCase
{
    public function test_non_completed_order_cannot_generate_report(): void
    {
        $context = $this->authenticatedReportContext(
            'report-incomplete'
        );

        $order = $this->createProcessingOrder(
            $context['tenant'],
            $context['headers']
        );

        $this
            ->generateReport(
                $context['headers'],
                $order
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'laboratory_order'
            );
    }

    public function test_non_approved_result_blocks_generation(): void
    {
        $context = $this->authenticatedReportContext(
            'report-unapproved'
        );

        $order = $this->createReportReadyOrder(
            $context['tenant'],
            $context['headers']
        );

        $this->runInTenant(
            $context['tenant'],
            static function () use ($order): void {
                TestResult::query()
                    ->whereHas(
                        'orderItem',
                        static fn ($query) => (
                            $query->where(
                                'laboratory_order_id',
                                $order->id
                            )
                        )
                    )
                    ->firstOrFail()
                    ->updateQuietly([
                        'status' => (
                            TestResult::STATUS_SUBMITTED
                        ),
                    ]);
            }
        );

        $this
            ->generateReport(
                $context['headers'],
                $order
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'laboratory_order'
            );
    }

    public function test_incomplete_active_item_blocks_generation(): void
    {
        $context = $this->authenticatedReportContext(
            'report-item-state'
        );

        $order = $this->createReportReadyOrder(
            $context['tenant'],
            $context['headers']
        );

        $this->runInTenant(
            $context['tenant'],
            static function () use ($order): void {
                LaboratoryOrderItem::query()
                    ->where(
                        'laboratory_order_id',
                        $order->id
                    )
                    ->firstOrFail()
                    ->update([
                        'status' => 'in_progress',
                        'completed_at' => null,
                    ]);
            }
        );

        $this
            ->generateReport(
                $context['headers'],
                $order
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors(
                'laboratory_order'
            );
    }
}
