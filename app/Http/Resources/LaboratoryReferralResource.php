<?php

namespace App\Http\Resources;

use App\Models\LaboratoryReferral;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryReferralResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $item = $this->relationLoaded('orderItem')
            ? $this->orderItem
            : null;
        $order = $item?->relationLoaded('order')
            ? $item->order
            : null;
        $priceList = $order?->relationLoaded('priceList')
            ? $order->priceList
            : null;
        $cost = $this->final_cost ?? $this->quoted_cost;
        $margin = $priceList !== null
            && strtoupper((string) $priceList->currency)
                === strtoupper((string) $this->currency)
                ? number_format(
                    (float) $item->price_snapshot - (float) $cost,
                    2,
                    '.',
                    ''
                )
                : null;

        return [
            'uuid' => $this->uuid,
            'referral_number' => $this->referral_number,
            'status' => $this->status,
            'order_item' => $item === null
                ? null
                : [
                    'uuid' => $item->uuid,
                    'test_name' => $item->test_name_snapshot,
                    'category' => $item->test_category_snapshot,
                    'status' => $item->status,
                    'patient_charge' => $item->price_snapshot,
                    'order' => $order === null
                        ? null
                        : [
                            'uuid' => $order->uuid,
                            'order_number' => $order->order_number,
                            'priority' => $order->priority,
                            'status' => $order->status,
                            'patient' => (
                                $order->relationLoaded('patient')
                                && $order->patient !== null
                                    ? [
                                        'id' => $order->patient->id,
                                        'medical_record_number' => (
                                            $order->patient
                                                ->medical_record_number
                                        ),
                                        'first_name' => (
                                            $order->patient->first_name
                                        ),
                                        'last_name' => (
                                            $order->patient->last_name
                                        ),
                                        'full_name' => (
                                            $order->patient->full_name
                                        ),
                                    ]
                                    : null
                            ),
                        ],
                ],
            'dispatch_branch' => $this->when(
                $this->relationLoaded('dispatchBranch'),
                fn (): ?array => $this->dispatchBranch === null
                    ? null
                    : [
                        'uuid' => $this->dispatchBranch->uuid,
                        'code' => $this->dispatchBranch->code,
                        'name' => $this->dispatchBranch->name,
                        'type' => $this->dispatchBranch->type,
                    ]
            ),
            'created_by' => $this->when(
                $this->relationLoaded('creator'),
                fn (): ?array => $this->creator === null
                    ? null
                    : [
                        'id' => $this->creator->getKey(),
                        'name' => $this->creator->name,
                        'email' => $this->creator->email,
                    ]
            ),
            'laboratory' => $this->laboratory_snapshot,
            'test' => $this->test_snapshot,
            'quoted_cost' => $this->quoted_cost,
            'final_cost' => $this->final_cost,
            'currency' => $this->currency,
            'margin' => $margin,
            'external_reference' => $this->external_reference,
            'dispatch_notes' => $this->dispatch_notes,
            'turnaround_due_at' => (
                $this->turnaround_due_at?->toISOString()
            ),
            'is_overdue' => (
                $this->turnaround_due_at !== null
                && ! in_array(
                    $this->status,
                    LaboratoryReferral::TERMINAL_STATUSES,
                    true
                )
                && $this->turnaround_due_at->isPast()
            ),
            'specimens' => $this->when(
                $this->relationLoaded('specimens'),
                fn (): array => $this->specimens
                    ->map(
                        fn ($specimen): array => [
                            'uuid' => $specimen->uuid,
                            'snapshot' => $this->decodeSnapshot(
                                $specimen->pivot?->specimen_snapshot
                            ),
                        ]
                    )
                    ->values()
                    ->all()
            ),
            'latest_external_result' => $this->when(
                $this->relationLoaded('latestExternalResult'),
                fn (): ?array => $this->latestExternalResult === null
                    ? null
                    : [
                        'uuid' => $this->latestExternalResult->uuid,
                        'version' => $this->latestExternalResult->version,
                        'external_report_number' => (
                            $this->latestExternalResult
                                ->external_report_number
                        ),
                        'reported_at' => (
                            $this->latestExternalResult
                                ->reported_at
                                ?->toISOString()
                        ),
                        'received_at' => (
                            $this->latestExternalResult
                                ->received_at
                                ?->toISOString()
                        ),
                        'source_payload_hash' => (
                            $this->latestExternalResult
                                ->source_payload_hash
                        ),
                    ]
            ),
            'standard_result' => $this->when(
                $this->relationLoaded('latestExternalResult'),
                function (): ?array {
                    $external = $this->latestExternalResult;

                    if (
                        $external === null
                        || ! $external->relationLoaded('testResult')
                        || $external->testResult === null
                    ) {
                        return null;
                    }

                    return [
                        'uuid' => $external->testResult->uuid,
                        'status' => $external->testResult->status,
                    ];
                }
            ),
            'external_results' => LaboratoryReferralResultResource::collection(
                $this->whenLoaded('externalResults')
            ),
            'timeline' => LaboratoryReferralEventResource::collection(
                $this->whenLoaded('events')
            ),
            'timestamps' => [
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
                'dispatched_at' => $this->dispatched_at?->toISOString(),
                'acknowledged_at' => (
                    $this->acknowledged_at?->toISOString()
                ),
                'result_received_at' => (
                    $this->result_received_at?->toISOString()
                ),
                'completed_at' => $this->completed_at?->toISOString(),
                'rejected_at' => $this->rejected_at?->toISOString(),
                'cancelled_at' => $this->cancelled_at?->toISOString(),
            ],
            'rejection_reason' => $this->rejection_reason,
            'cancellation_reason' => $this->cancellation_reason,
            'is_terminal' => in_array(
                $this->status,
                LaboratoryReferral::TERMINAL_STATUSES,
                true
            ),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeSnapshot(mixed $snapshot): ?array
    {
        if (is_array($snapshot)) {
            return $snapshot;
        }

        if (! is_string($snapshot) || $snapshot === '') {
            return null;
        }

        $decoded = json_decode($snapshot, true);

        return is_array($decoded) ? $decoded : null;
    }
}
