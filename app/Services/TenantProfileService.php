<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantProfile;
use Illuminate\Http\UploadedFile;

class TenantProfileService
{
    public function __construct()
    {
        //
    }

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    /**
     * Get the laboratory profile for a tenant.
     * Creates one automatically if it does not exist.
     */
    public function getProfile(Tenant $tenant): TenantProfile
    {
        return TenantProfile::query()->firstOrCreate(
            [
                'tenant_id' => $tenant->id,
            ],
            [
                'lab_name' => $tenant->name,
            ]
        );
    }

    /**
     * Update laboratory profile.
     */
    public function updateProfile(
        Tenant $tenant,
        array $data
    ): TenantProfile {

        $profile = $this->getProfile($tenant);

        $profile->update($data);

        return $profile->fresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */

    /**
     * Upload or replace media for the laboratory profile.
     *
     * Supported collections include:
     *  - logo
     *  - favicon
     *  - director_signature
     *  - accreditation_certificate
     *  - cac_certificate
     *  - iso_certificate
     *  - etc.
     */
    public function uploadMedia(
        Tenant $tenant,
        UploadedFile $file,
        string $collection
    ): TenantProfile {

        $profile = $this->getProfile($tenant);

        $profile->replaceMedia(
            $file,
            $collection
        );

        return $profile->fresh();
    }

    /**
     * Remove all media from a collection.
     */
    public function deleteMedia(
        Tenant $tenant,
        string $collection
    ): TenantProfile {

        $profile = $this->getProfile($tenant);

        $profile->clearMediaCollection(
            $collection
        );

        return $profile->fresh();
    }
}