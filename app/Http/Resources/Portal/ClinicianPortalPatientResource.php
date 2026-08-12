<?php

namespace App\Http\Resources\Portal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicianPortalPatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'medical_record_number' => (
                $this->medical_record_number
            ),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'date_of_birth' => (
                $this->date_of_birth?->toDateString()
            ),
            'gender' => $this->gender,
        ];
    }
}
