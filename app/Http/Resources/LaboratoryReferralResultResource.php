<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LaboratoryReferralResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'version' => (int) $this->version,
            'supersedes_uuid' => $this->when(
                $this->relationLoaded('supersededResult'),
                fn (): ?string => $this->supersededResult?->uuid
            ),
            'external_report_number' => $this->external_report_number,
            'results_data' => $this->results_data ?? [],
            'notes' => $this->notes,
            'reported_at' => $this->reported_at?->toISOString(),
            'received_at' => $this->received_at?->toISOString(),
            'receiver' => $this->when(
                $this->relationLoaded('receiver'),
                fn (): ?array => $this->receiver === null
                    ? null
                    : [
                        'id' => (int) $this->receiver->getKey(),
                        'name' => $this->receiver->name,
                        'email' => $this->receiver->email,
                    ]
            ),
            'receiver_snapshot' => $this->receiver_snapshot,
            'source_payload_hash' => $this->source_payload_hash,
            'test_result' => $this->when(
                $this->relationLoaded('testResult'),
                fn (): ?array => $this->testResult === null
                    ? null
                    : [
                        'uuid' => $this->testResult->uuid,
                        'status' => $this->testResult->status,
                    ]
            ),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
