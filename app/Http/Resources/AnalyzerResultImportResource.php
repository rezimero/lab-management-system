<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyzerResultImportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'source_result_key' => $this->source_result_key,
            'source_sample_identifier' => (
                $this->source_sample_identifier
            ),
            'instrument_test_code' => $this->instrument_test_code,
            'status' => $this->status,
            'normalized_results' => $this->normalized_results,
            'mapping_snapshot' => $this->mapping_snapshot,
            'validation_issues' => $this->validation_issues,
            'attempts' => (int) $this->attempts,
            'last_processed_at' => (
                $this->last_processed_at?->toISOString()
            ),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'rejected_at' => $this->rejected_at?->toISOString(),
            'review_note' => $this->review_note,

            'instrument' => $this->when(
                $this->relationLoaded('instrument'),
                fn (): ?array => $this->instrument === null
                    ? null
                    : [
                        'uuid' => $this->instrument->uuid,
                        'code' => $this->instrument->code,
                        'name' => $this->instrument->name,
                    ]
            ),

            'message' => $this->when(
                $this->relationLoaded('message'),
                fn (): ?array => $this->message === null
                    ? null
                    : [
                        'uuid' => $this->message->uuid,
                        'external_message_id' => (
                            $this->message->external_message_id
                        ),
                        'status' => $this->message->status,
                        'received_at' => (
                            $this->message->received_at?->toISOString()
                        ),
                    ]
            ),

            'specimen' => $this->when(
                $this->relationLoaded('specimen'),
                fn (): ?array => $this->specimen === null
                    ? null
                    : [
                        'uuid' => $this->specimen->uuid,
                        'accession_number' => (
                            $this->specimen->accession_number
                        ),
                        'status' => $this->specimen->status,
                    ]
            ),

            'order_item' => $this->when(
                $this->relationLoaded('orderItem'),
                fn (): ?array => $this->orderItem === null
                    ? null
                    : [
                        'uuid' => $this->orderItem->uuid,
                        'test_name' => (
                            $this->orderItem->test_name_snapshot
                        ),
                        'status' => $this->orderItem->status,
                        'order_uuid' => (
                            $this->orderItem->order?->uuid
                        ),
                        'order_number' => (
                            $this->orderItem->order?->order_number
                        ),
                    ]
            ),

            'test_result' => $this->when(
                $this->relationLoaded('testResult'),
                fn (): ?array => $this->testResult === null
                    ? null
                    : [
                        'uuid' => $this->testResult->uuid,
                        'status' => $this->testResult->status,
                    ]
            ),

            'events' => AnalyzerImportEventResource::collection(
                $this->whenLoaded('events')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
