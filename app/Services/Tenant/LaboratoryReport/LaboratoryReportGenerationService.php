<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Models\LaboratoryOrder;
use App\Models\LaboratoryReport;
use App\Models\Tenant;
use App\Services\TenantProfileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

class LaboratoryReportGenerationService
{
    public function __construct(
        private readonly LaboratoryReportEligibilityService
            $eligibilityService,
        private readonly LaboratoryReportSnapshotService
            $snapshotService,
        private readonly TenantProfileService
            $tenantProfileService
    ) {
    }

    /**
     * Generate one immutable report for an order.
     */
    public function generate(
        string $orderUuid,
        int $userId
    ): LaboratoryReport {
        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            throw new LogicException(
                'No active tenant is available.'
            );
        }

        return DB::connection('tenant')->transaction(
            function () use (
                $orderUuid,
                $userId,
                $tenant
            ): LaboratoryReport {
                $order = LaboratoryOrder::query()
                    ->where('uuid', $orderUuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                $order->load([
                    'patient',
                    'orderedBy',
                    'items' => (
                        static fn ($query) => (
                            $query->orderBy('id')
                        )
                    ),
                    'items.testResult.enteredBy',
                    'items.testResult.submittedBy',
                    'items.testResult.reviewedBy',
                ]);

                $this->assertNotGenerated(
                    $order
                );

                $this->eligibilityService
                    ->assertEligible(
                        $order
                    );

                $profile = $this
                    ->tenantProfileService
                    ->getProfile(
                        $tenant
                    );

                $snapshots = $this
                    ->snapshotService
                    ->build(
                        $profile,
                        $order
                    );

                $report = LaboratoryReport::query()
                    ->create([
                        'report_number' => (
                            'RPT-'.$order->order_number
                        ),
                        'laboratory_order_id' => (
                            $order->id
                        ),
                        'generated_by' => $userId,
                        'laboratory_snapshot' => (
                            $snapshots[
                                'laboratory_snapshot'
                            ]
                        ),
                        'patient_snapshot' => (
                            $snapshots[
                                'patient_snapshot'
                            ]
                        ),
                        'order_snapshot' => (
                            $snapshots[
                                'order_snapshot'
                            ]
                        ),
                        'results_snapshot' => (
                            $snapshots[
                                'results_snapshot'
                            ]
                        ),
                        'generated_at' => now(),
                    ]);

                return $report->load(
                    'generatedBy'
                );
            }
        );
    }

    /**
     * Prevent duplicate report issuance.
     */
    private function assertNotGenerated(
        LaboratoryOrder $order
    ): void {
        $exists = LaboratoryReport::query()
            ->where(
                'laboratory_order_id',
                $order->id
            )
            ->exists();

        if (! $exists) {
            return;
        }

        throw ValidationException::withMessages([
            'laboratory_order' => (
                'A laboratory report has already been '
                .'generated for this order.'
            ),
        ]);
    }
}
