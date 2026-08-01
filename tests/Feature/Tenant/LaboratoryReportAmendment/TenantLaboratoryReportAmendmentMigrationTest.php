<?php

namespace Tests\Feature\Tenant\LaboratoryReportAmendment;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\Tenant\LaboratoryReportAmendmentTestCase;

class TenantLaboratoryReportAmendmentMigrationTest extends LaboratoryReportAmendmentTestCase
{
    public function test_legacy_report_is_backfilled_as_version_one(): void
    {
        $context = $this->authenticatedReportContext(
            'report-amendment-migration'
        );

        $result = $this->runInTenant(
            $context['tenant'],
            function (): array {
                $migration = require database_path(
                    'migrations/tenant/'
                    .'2026_08_01_080000_'
                    .'add_laboratory_report_amendment_workflow.php'
                );

                $migration->down();

                $this->assertFalse(
                    Schema::connection('tenant')->hasTable(
                        'laboratory_report_amendments'
                    )
                );
                $this->assertFalse(
                    Schema::connection('tenant')->hasColumn(
                        'laboratory_reports',
                        'lineage_uuid'
                    )
                );

                $now = now();
                $userId = (int) DB::connection('tenant')
                    ->table('users')
                    ->value('id');

                $this->assertGreaterThan(0, $userId);

                $patientId = DB::connection('tenant')
                    ->table('patients')
                    ->insertGetId([
                        'first_name' => 'Legacy',
                        'last_name' => 'Patient',
                        'date_of_birth' => '1990-01-01',
                        'gender' => 'other',
                        'email' => null,
                        'phone' => '+2348000000001',
                        'address' => null,
                        'medical_record_number' => (
                            'MRN-LEGACY-'.Str::upper(
                                Str::random(8)
                            )
                        ),
                        'notes' => null,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                $orderUuid = (string) Str::uuid();
                $orderNumber = (
                    'ORD-LEGACY-'.Str::upper(
                        Str::random(8)
                    )
                );

                $orderId = DB::connection('tenant')
                    ->table('laboratory_orders')
                    ->insertGetId([
                        'uuid' => $orderUuid,
                        'order_number' => $orderNumber,
                        'patient_id' => $patientId,
                        'ordered_by' => $userId,
                        'referring_clinician' => null,
                        'priority' => 'routine',
                        'status' => 'completed',
                        'clinical_notes' => null,
                        'total_amount' => '0.00',
                        'ordered_at' => $now,
                        'sample_collected_at' => $now,
                        'completed_at' => $now,
                        'cancelled_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ]);

                $reportUuid = (string) Str::uuid();

                DB::connection('tenant')
                    ->table('laboratory_reports')
                    ->insert([
                        'uuid' => $reportUuid,
                        'report_number' => (
                            'RPT-'.$orderNumber
                        ),
                        'laboratory_order_id' => $orderId,
                        'generated_by' => $userId,
                        'laboratory_snapshot' => json_encode(
                            ['lab_name' => 'Legacy Laboratory'],
                            JSON_THROW_ON_ERROR
                        ),
                        'patient_snapshot' => json_encode(
                            [
                                'medical_record_number' => (
                                    'MRN-LEGACY'
                                ),
                                'first_name' => 'Legacy',
                                'last_name' => 'Patient',
                                'full_name' => 'Legacy Patient',
                            ],
                            JSON_THROW_ON_ERROR
                        ),
                        'order_snapshot' => json_encode(
                            [
                                'uuid' => $orderUuid,
                                'order_number' => $orderNumber,
                                'status' => 'completed',
                            ],
                            JSON_THROW_ON_ERROR
                        ),
                        'results_snapshot' => json_encode(
                            ['items' => []],
                            JSON_THROW_ON_ERROR
                        ),
                        'generated_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                $migration->up();

                $report = DB::connection('tenant')
                    ->table('laboratory_reports')
                    ->where('uuid', $reportUuid)
                    ->first();

                $this->assertNotNull($report);
                $this->assertSame(
                    $reportUuid,
                    $report->lineage_uuid
                );
                $this->assertSame(1, (int) $report->version);
                $this->assertNull($report->supersedes_report_id);
                $this->assertNull($report->amendment_reason);

                return [
                    'has_workflow_table' => (
                        Schema::connection('tenant')->hasTable(
                            'laboratory_report_amendments'
                        )
                    ),
                    'has_result_table' => (
                        Schema::connection('tenant')->hasTable(
                            'test_result_amendments'
                        )
                    ),
                    'lineage_index_count' => (int) DB::connection(
                        'tenant'
                    )->table('information_schema.statistics')
                        ->whereRaw('table_schema = DATABASE()')
                        ->where(
                            'table_name',
                            'laboratory_reports'
                        )
                        ->where(
                            'index_name',
                            'lab_reports_lineage_version_unique'
                        )
                        ->count(),
                ];
            }
        );

        $this->assertTrue($result['has_workflow_table']);
        $this->assertTrue($result['has_result_table']);
        $this->assertGreaterThanOrEqual(
            1,
            $result['lineage_index_count']
        );
    }
}
