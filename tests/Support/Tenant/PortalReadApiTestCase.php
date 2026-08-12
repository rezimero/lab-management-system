<?php

namespace Tests\Support\Tenant;

use App\Models\LaboratoryOrder;
use App\Models\LaboratoryReport;
use App\Models\Patient;
use App\Models\ReferringClinician;
use App\Models\Tenant;
use App\Models\TestResult;
use Illuminate\Support\Str;

abstract class PortalReadApiTestCase extends PortalAuthenticationTestCase
{
    protected function loginPatientPortal(
        array $tenant,
        array $identity
    ): string {
        $response = $this->postJson(
            '/api/portal/patient/login',
            [
                'domain' => $tenant['domain'],
                'email' => $identity['email'],
                'password' => $identity['password'],
            ]
        )->assertOk();

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        return $token;
    }

    protected function loginClinicianPortal(
        array $tenant,
        array $identity
    ): string {
        $response = $this->postJson(
            '/api/portal/clinician/login',
            [
                'domain' => $tenant['domain'],
                'email' => $identity['email'],
                'password' => $identity['password'],
            ]
        )->assertOk();

        $token = $response->json('token');

        $this->assertIsString($token);
        $this->assertNotSame('', $token);

        return $token;
    }

    protected function createPortalOrder(
        Tenant $tenant,
        Patient $patient,
        ?ReferringClinician $clinician = null,
        array $overrides = []
    ): LaboratoryOrder {
        return $this->runInPortalTenant(
            $tenant,
            function () use (
                $patient,
                $clinician,
                $overrides
            ): LaboratoryOrder {
                $order = LaboratoryOrder::query()->create(
                    array_merge(
                        [
                            'order_number' => (
                                'PORTAL-'
                                .strtoupper(
                                    Str::random(10)
                                )
                            ),
                            'patient_id' => $patient->getKey(),
                            'ordered_by' => null,
                            'referring_clinician_id' => (
                                $clinician?->getKey()
                            ),
                            'referring_clinician' => (
                                $clinician?->name
                            ),
                            'priority' => 'routine',
                            'status' => 'in_progress',
                            'clinical_notes' => (
                                'INTERNAL CLINICAL NOTE'
                            ),
                            'total_amount' => '25000.00',
                            'ordered_at' => now(),
                        ],
                        $overrides
                    )
                );

                $order->items()->create([
                    'lab_test_id' => null,
                    'test_name_snapshot' => 'Portal Test',
                    'test_category_snapshot' => 'General',
                    'price_snapshot' => '25000.00',
                    'turnaround_time_hours_snapshot' => 24,
                    'parameters_snapshot' => [
                        [
                            'name' => 'Marker',
                            'unit' => 'mg/L',
                            'reference_range' => '1-5',
                        ],
                    ],
                    'status' => 'in_progress',
                ]);

                return $order
                    ->fresh()
                    ->load('items');
            }
        );
    }

    protected function addInternalResult(
        Tenant $tenant,
        LaboratoryOrder $order,
        string $status,
        string $value
    ): TestResult {
        return $this->runInPortalTenant(
            $tenant,
            function () use (
                $order,
                $status,
                $value
            ): TestResult {
                $item = $order->items()
                    ->firstOrFail();

                return TestResult::query()->create([
                    'laboratory_order_item_id' => (
                        $item->getKey()
                    ),
                    'results_data' => [
                        [
                            'name' => 'Marker',
                            'value' => $value,
                            'unit' => 'mg/L',
                            'reference_range' => '1-5',
                            'flag' => 'high',
                        ],
                    ],
                    'notes' => 'INTERNAL RESULT NOTE',
                    'status' => $status,
                    'entered_by' => null,
                    'submitted_by' => null,
                    'reviewed_by' => null,
                ]);
            }
        );
    }

    protected function createPortalReport(
        Tenant $tenant,
        LaboratoryOrder $order,
        int $version = 1,
        ?LaboratoryReport $supersedes = null,
        array $overrides = []
    ): LaboratoryReport {
        return $this->runInPortalTenant(
            $tenant,
            function () use (
                $order,
                $version,
                $supersedes,
                $overrides
            ): LaboratoryReport {
                $uuid = (string) Str::uuid();
                $lineageUuid = (
                    $supersedes?->lineage_uuid
                    ?? $uuid
                );

                return LaboratoryReport::query()->create(
                    array_merge(
                        [
                            'uuid' => $uuid,
                            'lineage_uuid' => $lineageUuid,
                            'version' => $version,
                            'report_number' => (
                                'RPT-PORTAL-'
                                .strtoupper(
                                    Str::random(8)
                                )
                                .(
                                    $version > 1
                                        ? '-A'.($version - 1)
                                        : ''
                                )
                            ),
                            'laboratory_order_id' => (
                                $order->getKey()
                            ),
                            'supersedes_report_id' => (
                                $supersedes?->getKey()
                            ),
                            'generated_by' => null,
                            'laboratory_snapshot' => [
                                'lab_name' => 'Portal Laboratory',
                                'tagline' => 'Trusted diagnostics',
                                'email' => 'lab@example.test',
                                'phone' => '+2348000000000',
                                'website' => 'https://example.test',
                                'address' => '1 Laboratory Road',
                                'full_address' => '1 Laboratory Road, Abuja',
                                'city' => 'Abuja',
                                'state' => 'FCT',
                                'country' => 'Nigeria',
                                'license_number' => 'LAB-100',
                                'registration_number' => 'REG-100',
                                'tin' => 'INTERNAL-TIN',
                                'report_footer' => 'Clinical report footer',
                                'director_name' => 'Dr Director',
                                'director_qualification' => 'MBBS',
                                'director_registration_number' => 'MDCN-1',
                                'signatory_designation' => 'Pathologist',
                                'accreditation_details' => 'ISO 15189',
                                'logo_url' => '/internal/logo/path',
                                'director_signature_url' => (
                                    '/internal/signature/path'
                                ),
                            ],
                            'patient_snapshot' => [
                                'id' => $order->patient_id,
                                'medical_record_number' => (
                                    $order->patient
                                        ?->medical_record_number
                                ),
                                'first_name' => (
                                    $order->patient?->first_name
                                ),
                                'last_name' => (
                                    $order->patient?->last_name
                                ),
                                'full_name' => (
                                    $order->patient?->full_name
                                ),
                                'date_of_birth' => (
                                    $order->patient
                                        ?->date_of_birth
                                        ?->toDateString()
                                ),
                                'gender' => (
                                    $order->patient?->gender
                                ),
                                'email' => 'PRIVATE-PATIENT-EMAIL@example.test',
                                'phone' => 'PRIVATE-PATIENT-PHONE',
                                'address' => 'PRIVATE-PATIENT-ADDRESS',
                            ],
                            'order_snapshot' => [
                                'uuid' => $order->uuid,
                                'order_number' => $order->order_number,
                                'priority' => $order->priority,
                                'status' => 'completed',
                                'referring_clinician' => (
                                    $order->referring_clinician
                                ),
                                'clinical_notes' => (
                                    'INTERNAL REPORT CLINICAL NOTE'
                                ),
                                'total_amount' => '25000.00',
                                'ordered_at' => (
                                    $order->ordered_at
                                        ?->toISOString()
                                ),
                                'sample_collected_at' => null,
                                'completed_at' => now()->toISOString(),
                                'ordered_by' => [
                                    'id' => 999,
                                    'name' => 'INTERNAL USER',
                                    'email' => (
                                        'internal@example.test'
                                    ),
                                ],
                            ],
                            'results_snapshot' => [
                                'items' => [
                                    [
                                        'order_item_uuid' => (
                                            $order->items
                                                ->first()
                                                ?->uuid
                                        ),
                                        'test_name' => 'Portal Test',
                                        'category' => 'General',
                                        'parameters' => [
                                            [
                                                'name' => 'Marker',
                                                'unit' => 'mg/L',
                                                'reference_range' => '1-5',
                                            ],
                                        ],
                                        'result_uuid' => (
                                            (string) Str::uuid()
                                        ),
                                        'effective_version' => $version,
                                        'result_amendment_uuid' => (
                                            $version > 1
                                                ? (string) Str::uuid()
                                                : null
                                        ),
                                        'results_data' => [
                                            [
                                                'name' => 'Marker',
                                                'value' => (
                                                    $version > 1
                                                        ? '7'
                                                        : '6'
                                                ),
                                                'unit' => 'mg/L',
                                                'reference_range' => '1-5',
                                                'flag' => 'high',
                                            ],
                                        ],
                                        'notes' => 'Issued clinical note',
                                        'entered_by' => [
                                            'id' => 999,
                                            'name' => 'INTERNAL ENTERER',
                                            'email' => 'enterer@example.test',
                                        ],
                                        'submitted_by' => [
                                            'id' => 998,
                                            'name' => 'INTERNAL SUBMITTER',
                                            'email' => 'submitter@example.test',
                                        ],
                                        'reviewed_by' => [
                                            'id' => 997,
                                            'name' => 'INTERNAL REVIEWER',
                                            'email' => 'reviewer@example.test',
                                        ],
                                        'submitted_at' => now()->toISOString(),
                                        'reviewed_at' => now()->toISOString(),
                                    ],
                                ],
                            ],
                            'amendment_reason' => (
                                $version > 1
                                    ? 'Corrected issued value'
                                    : null
                            ),
                            'amendment_summary' => (
                                $version > 1
                                    ? [
                                        'internal_hash' => (
                                            'DO-NOT-EXPOSE'
                                        ),
                                    ]
                                    : null
                            ),
                            'amended_by' => null,
                            'amendment_actor_snapshot' => (
                                $version > 1
                                    ? [
                                        'id' => 999,
                                        'name' => 'INTERNAL REVIEWER',
                                        'email' => 'reviewer@example.test',
                                    ]
                                    : null
                            ),
                            'amended_at' => (
                                $version > 1
                                    ? now()
                                    : null
                            ),
                            'generated_at' => now(),
                        ],
                        $overrides
                    )
                );
            }
        );
    }
}
