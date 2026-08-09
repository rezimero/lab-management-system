<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferralLaboratoryTestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'laboratory' => $this->whenLoaded(
                'referralLaboratory',
                fn (): array => [
                    'uuid' => $this->referralLaboratory->uuid,
                    'code' => $this->referralLaboratory->code,
                    'name' => $this->referralLaboratory->name,
                    'is_active' => (bool) $this->referralLaboratory->is_active,
                ]
            ),
            'lab_test' => $this->whenLoaded(
                'labTest',
                fn (): array => [
                    'uuid' => $this->labTest->uuid,
                    'name' => $this->labTest->name,
                    'category' => $this->labTest->category,
                    'is_active' => (bool) $this->labTest->is_active,
                ]
            ),
            'external_test_code' => $this->external_test_code,
            'external_test_name' => $this->external_test_name,
            'cost' => $this->cost,
            'currency' => $this->currency,
            'turnaround_hours' => $this->turnaround_hours,
            'specimen_requirements' => $this->specimen_requirements,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
