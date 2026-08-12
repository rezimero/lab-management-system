<?php

namespace App\Http\Resources\Portal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicianPortalAccessSubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'subject_type' => 'clinician',
            'subject' => [
                'uuid' => $this->uuid,
                'name' => $this->name,
                'email' => $this->email,
                'identifier' => $this->registration_number,
                'organisation' => $this->organisation,
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
