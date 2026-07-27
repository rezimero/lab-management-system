<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantProfileResource extends JsonResource
{
    /**
     * Transform the administrator-only laboratory profile.
     *
     * @return array<string, mixed>
     */
    public function toArray(
        Request $request
    ): array {
        return [
            'lab_name' => $this->lab_name,
            'tagline' => $this->tagline,

            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'accent_color' => $this->accent_color,

            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,

            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'full_address' => $this->full_address,

            'license_number' => $this->license_number,
            'registration_number' => (
                $this->registration_number
            ),
            'tin' => $this->tin,

            'report_footer' => $this->report_footer,
            'director_name' => $this->director_name,

            'invoice_footer' => $this->invoice_footer,
            'bank_name' => $this->bank_name,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,

            'logo_url' => $this->logo_url,
            'favicon_url' => $this->favicon_url,
            'director_signature_url' => (
                $this->director_signature_url
            ),

            'created_at' => $this->created_at
                ?->toISOString(),
            'updated_at' => $this->updated_at
                ?->toISOString(),
        ];
    }
}