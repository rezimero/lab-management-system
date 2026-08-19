<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyzerInstrumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'manufacturer' => $this->manufacturer,
            'model' => $this->model,
            'serial_number' => $this->serial_number,
            'adapter_key' => $this->adapter_key,
            'transport_mode' => $this->transport_mode,
            'machine_token_hint' => $this->machine_token_hint,
            'token_rotated_at' => $this->token_rotated_at?->toISOString(),
            'is_active' => (bool) $this->is_active,
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'last_success_at' => $this->last_success_at?->toISOString(),
            'last_error_at' => $this->last_error_at?->toISOString(),
            'last_error_code' => $this->last_error_code,
            'notes' => $this->notes,
            'branch' => $this->whenLoaded(
                'branch',
                fn (): array => [
                    'uuid' => $this->branch->uuid,
                    'code' => $this->branch->code,
                    'name' => $this->branch->name,
                    'type' => $this->branch->type,
                ]
            ),
            'test_mappings_count' => $this->whenCounted(
                'testMappings'
            ),
            'messages_count' => $this->whenCounted('messages'),
            'imports_count' => $this->whenCounted('imports'),
            'test_mappings' => AnalyzerTestMappingResource::collection(
                $this->whenLoaded('testMappings')
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
