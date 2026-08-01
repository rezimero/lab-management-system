<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Models\LaboratoryOrder;
use App\Models\LaboratoryOrderItem;
use App\Models\Patient;
use App\Models\TenantProfile;
use App\Models\TestResult;
use Illuminate\LogicException;

class LaboratoryReportSnapshotService
{
    /**
     * Build all immutable report snapshots.
     *
     * @return array<string, array<string, mixed>>
     */
    public function build(
        TenantProfile $profile,
        LaboratoryOrder $order
    ): array {
        return [
            'laboratory_snapshot' => (
                $this->laboratorySnapshot(
                    $profile
                )
            ),

            'patient_snapshot' => (
                $this->patientSnapshot(
                    $order->patient
                )
            ),

            'order_snapshot' => (
                $this->orderSnapshot(
                    $order
                )
            ),

            'results_snapshot' => [
                'items' => (
                    $this->resultSnapshots(
                        $order
                    )
                ),
            ],
        ];
    }

    /**
     * Snapshot laboratory branding and identity.
     *
     * @return array<string, mixed>
     */
    private function laboratorySnapshot(
        TenantProfile $profile
    ): array {
        return [
            'lab_name' => $profile->lab_name,
            'tagline' => $profile->tagline,
            'primary_color' => $profile->primary_color,
            'secondary_color' => $profile->secondary_color,
            'accent_color' => $profile->accent_color,
            'email' => $profile->email,
            'phone' => $profile->phone,
            'website' => $profile->website,
            'address' => $profile->address,
            'city' => $profile->city,
            'state' => $profile->state,
            'country' => $profile->country,
            'full_address' => $profile->full_address,
            'license_number' => $profile->license_number,
            'registration_number' => (
                $profile->registration_number
            ),
            'tin' => $profile->tin,
            'report_footer' => $profile->report_footer,
            'director_name' => $profile->director_name,
            'director_qualification' => (
                $profile->director_qualification
            ),
            'director_registration_number' => (
                $profile->director_registration_number
            ),
            'signatory_designation' => (
                $profile->signatory_designation
            ),
            'accreditation_details' => (
                $profile->accreditation_details
            ),
            'logo_url' => $profile->logo_url,
            'director_signature_url' => (
                $profile->director_signature_url
            ),
        ];
    }

    /**
     * Snapshot patient identity.
     *
     * @return array<string, mixed>
     */
    private function patientSnapshot(
        ?Patient $patient
    ): array {
        if ($patient === null) {
            throw new LogicException(
                'Cannot snapshot a missing patient.'
            );
        }

        return [
            'id' => $patient->id,
            'medical_record_number' => (
                $patient->medical_record_number
            ),
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'full_name' => $patient->full_name,
            'date_of_birth' => (
                $patient->date_of_birth
                    ?->toDateString()
            ),
            'gender' => $patient->gender,
            'email' => $patient->email,
            'phone' => $patient->phone,
            'address' => $patient->address,
        ];
    }

    /**
     * Snapshot source-order information.
     *
     * @return array<string, mixed>
     */
    private function orderSnapshot(
        LaboratoryOrder $order
    ): array {
        return [
            'uuid' => $order->uuid,
            'order_number' => $order->order_number,
            'priority' => $order->priority,
            'status' => $order->status,
            'referring_clinician' => (
                $order->referring_clinician
            ),
            'clinical_notes' => $order->clinical_notes,
            'total_amount' => $order->total_amount,
            'ordered_at' => (
                $order->ordered_at
                    ?->toISOString()
            ),
            'sample_collected_at' => (
                $order->sample_collected_at
                    ?->toISOString()
            ),
            'completed_at' => (
                $order->completed_at
                    ?->toISOString()
            ),
            'ordered_by' => (
                $this->userSnapshot(
                    $order->orderedBy
                )
            ),
        ];
    }

    /**
     * Snapshot every active approved result.
     *
     * @return array<int, array<string, mixed>>
     */
    private function resultSnapshots(
        LaboratoryOrder $order
    ): array {
        return $order->items
            ->reject(
                static fn (
                    LaboratoryOrderItem $item
                ): bool => (
                    $item->status === 'cancelled'
                )
            )
            ->sortBy('id')
            ->values()
            ->map(
                function (
                    LaboratoryOrderItem $item
                ): array {
                    $result = $item->testResult;

                    if (! $result instanceof TestResult) {
                        throw new LogicException(
                            'Cannot snapshot a missing result.'
                        );
                    }

                    return [
                        'order_item_uuid' => $item->uuid,
                        'test_name' => (
                            $item->test_name_snapshot
                        ),
                        'category' => (
                            $item->test_category_snapshot
                        ),
                        'parameters' => (
                            $item->parameters_snapshot ?? []
                        ),
                        'result_uuid' => $result->uuid,
                        'effective_version' => 1,
                        'result_amendment_uuid' => null,
                        'results_data' => (
                            $result->results_data ?? []
                        ),
                        'notes' => $result->notes,
                        'entered_by' => (
                            $this->userSnapshot(
                                $result->enteredBy
                            )
                        ),
                        'submitted_by' => (
                            $this->userSnapshot(
                                $result->submittedBy
                            )
                        ),
                        'reviewed_by' => (
                            $this->userSnapshot(
                                $result->reviewedBy
                            )
                        ),
                        'submitted_at' => (
                            $result->submitted_at
                                ?->toISOString()
                        ),
                        'reviewed_at' => (
                            $result->reviewed_at
                                ?->toISOString()
                        ),
                    ];
                }
            )
            ->all();
    }

    /**
     * Snapshot a compact user identity.
     *
     * @return array<string, mixed>|null
     */
    private function userSnapshot(
        mixed $user
    ): ?array {
        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
}
