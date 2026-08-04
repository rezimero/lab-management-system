<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReferralLaboratoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'accreditation_number' => $this->accreditation_number,
            'contact_name' => $this->contact_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => [
                'line_1' => $this->address_line_1,
                'line_2' => $this->address_line_2,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
            ],
            'currency' => $this->currency,
            'default_turnaround_hours' => $this->default_turnaround_hours,
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes,
            'mapped_tests_count' => $this->whenCounted('testMappings'),
            'referrals_count' => $this->whenCounted('referrals'),
            'test_mappings' => ReferralLaboratoryTestResource::collection(
                $this->whenLoaded('testMappings')
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
