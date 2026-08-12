<?php

namespace App\Http\Resources\Portal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientPortalAccessSubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'subject_type' => 'patient',
            'subject' => [
                'id' => (int) $this->getKey(),
                'name' => $this->full_name,
                'email' => $this->email,
                'identifier' => $this->medical_record_number,
                'is_active' => (bool) $this->is_active,
            ],
            'account' => $this->relationLoaded('portalAccount')
                && $this->portalAccount !== null
                ? new PortalAccountAdministrationResource(
                    $this->portalAccount
                )
                : null,
        ];
    }
}
