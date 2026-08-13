<?php

namespace App\Http\Resources\Portal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PortalLaboratoryReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $version = max(
            1,
            (int) ($this->version ?? 1)
        );

        return [
            'uuid' => $this->uuid,
            'report_number' => $this->report_number,
            'version' => $version,
            'lifecycle_status' => $version > 1
                ? 'amended'
                : 'issued',
            'is_current' => true,
            'generated_at' => (
                $this->generated_at?->toISOString()
            ),
            'amended_at' => (
                $this->amended_at?->toISOString()
            ),

            'laboratory' => $this->laboratorySummary(),
            'patient' => $this->patientSummary(),
            'order' => $this->orderSummary(),
            'results' => $this->resultSummaries(),

            'amendment' => $version > 1
                ? [
                    'reason' => $this->amendment_reason,
                    'amended_at' => (
                        $this->amended_at?->toISOString()
                    ),
                ]
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function laboratorySummary(): array
    {
        $snapshot = is_array(
            $this->laboratory_snapshot
        )
            ? $this->laboratory_snapshot
            : [];

        return [
            'name' => $snapshot['lab_name'] ?? null,
            'tagline' => $snapshot['tagline'] ?? null,
            'email' => $snapshot['email'] ?? null,
            'phone' => $snapshot['phone'] ?? null,
            'website' => $snapshot['website'] ?? null,
            'address' => (
                $snapshot['full_address']
                ?? $snapshot['address']
                ?? null
            ),
            'city' => $snapshot['city'] ?? null,
            'state' => $snapshot['state'] ?? null,
            'country' => $snapshot['country'] ?? null,
            'license_number' => (
                $snapshot['license_number'] ?? null
            ),
            'registration_number' => (
                $snapshot['registration_number'] ?? null
            ),
            'accreditation_details' => (
                $snapshot['accreditation_details'] ?? null
            ),
            'report_footer' => (
                $snapshot['report_footer'] ?? null
            ),
            'director_name' => (
                $snapshot['director_name'] ?? null
            ),
            'director_qualification' => (
                $snapshot['director_qualification'] ?? null
            ),
            'director_registration_number' => (
                $snapshot[
                    'director_registration_number'
                ] ?? null
            ),
            'signatory_designation' => (
                $snapshot['signatory_designation'] ?? null
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function patientSummary(): array
    {
        $snapshot = is_array(
            $this->patient_snapshot
        )
            ? $this->patient_snapshot
            : [];

        return [
            'medical_record_number' => (
                $snapshot['medical_record_number'] ?? null
            ),
            'first_name' => $snapshot['first_name'] ?? null,
            'last_name' => $snapshot['last_name'] ?? null,
            'full_name' => $snapshot['full_name'] ?? null,
            'date_of_birth' => (
                $snapshot['date_of_birth'] ?? null
            ),
            'gender' => $snapshot['gender'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orderSummary(): array
    {
        $snapshot = is_array(
            $this->order_snapshot
        )
            ? $this->order_snapshot
            : [];

        return [
            'uuid' => $snapshot['uuid'] ?? null,
            'order_number' => (
                $snapshot['order_number'] ?? null
            ),
            'priority' => $snapshot['priority'] ?? null,
            'status' => $snapshot['status'] ?? null,
            'referring_clinician' => (
                $snapshot['referring_clinician'] ?? null
            ),
            'ordered_at' => (
                $snapshot['ordered_at'] ?? null
            ),
            'sample_collected_at' => (
                $snapshot['sample_collected_at'] ?? null
            ),
            'completed_at' => (
                $snapshot['completed_at'] ?? null
            ),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function resultSummaries(): array
    {
        $snapshot = is_array(
            $this->results_snapshot
        )
            ? $this->results_snapshot
            : [];

        $items = $snapshot['items'] ?? [];

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->filter(
                static fn (mixed $item): bool => (
                    is_array($item)
                )
            )
            ->map(
                static function (array $item): array {
                    return [
                        'order_item_uuid' => (
                            $item['order_item_uuid'] ?? null
                        ),
                        'test_name' => (
                            $item['test_name'] ?? null
                        ),
                        'category' => (
                            $item['category'] ?? null
                        ),
                        'parameters' => (
                            is_array(
                                $item['parameters'] ?? null
                            )
                                ? $item['parameters']
                                : []
                        ),
                        'effective_version' => max(
                            1,
                            (int) (
                                $item[
                                    'effective_version'
                                ] ?? 1
                            )
                        ),
                        'results_data' => (
                            is_array(
                                $item['results_data'] ?? null
                            )
                                ? $item['results_data']
                                : []
                        ),
                        'notes' => $item['notes'] ?? null,
                    ];
                }
            )
            ->values()
            ->all();
    }
}
