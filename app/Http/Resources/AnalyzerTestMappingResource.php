<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyzerTestMappingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'instrument_test_code' => $this->instrument_test_code,
            'instrument_test_name' => $this->instrument_test_name,
            'is_active' => (bool) $this->is_active,
            'instrument' => $this->whenLoaded(
                'instrument',
                fn (): array => [
                    'uuid' => $this->instrument->uuid,
                    'code' => $this->instrument->code,
                    'name' => $this->instrument->name,
                ]
            ),
            'lab_test' => $this->whenLoaded(
                'labTest',
                fn (): array => [
                    'uuid' => $this->labTest->uuid,
                    'name' => $this->labTest->name,
                    'category' => $this->labTest->category,
                    'parameters' => $this->labTest->parameters,
                    'is_active' => (bool) $this->labTest->is_active,
                ]
            ),
            'parameter_mappings_count' => $this->whenCounted(
                'parameterMappings'
            ),
            'parameter_mappings' => AnalyzerParameterMappingResource::collection(
                $this->whenLoaded('parameterMappings')
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
