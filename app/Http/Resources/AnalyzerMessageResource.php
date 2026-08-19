<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyzerMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'external_message_id' => $this->external_message_id,
            'adapter_key' => $this->adapter_key,
            'content_type' => $this->content_type,
            'payload_hash' => $this->payload_hash,
            'source_timestamp' => $this->source_timestamp?->toISOString(),
            'received_at' => $this->received_at?->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),
            'status' => $this->status,
            'last_error_code' => $this->last_error_code,
            'imports' => AnalyzerResultImportResource::collection(
                $this->whenLoaded('imports')
            ),
        ];
    }
}
