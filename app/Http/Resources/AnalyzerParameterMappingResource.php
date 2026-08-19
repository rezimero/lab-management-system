<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalyzerParameterMappingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'instrument_parameter_code' => (
                $this->instrument_parameter_code
            ),
            'instrument_parameter_name' => (
                $this->instrument_parameter_name
            ),
            'target_parameter_name' => (
                $this->target_parameter_name
            ),
            'expected_unit' => $this->expected_unit,
            'is_required' => (bool) $this->is_required,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
