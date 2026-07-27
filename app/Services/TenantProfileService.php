<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantProfile;
use Illuminate\Http\UploadedFile;

class TenantProfileService
{
    /**
     * Get the laboratory profile for a tenant.
     * Creates one automatically if it does not exist.
     */
    public function getProfile(
        Tenant $tenant
    ): TenantProfile {
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
     *
     * @param array<string, mixed> $data
     */
    public function updateProfile(
        Tenant $tenant,
        array $data
    ): TenantProfile {
        $profile = $this->getProfile($tenant);

        $profile->update($data);

        return $profile->fresh();
    }

    /**
     * Upload or replace the laboratory logo.
     */
    public function uploadLogo(
        Tenant $tenant,
        UploadedFile $file
    ): TenantProfile {
        return $this->uploadMedia(
            $tenant,
            $file,
            'logo'
        );
    }

    /**
     * Upload or replace the laboratory favicon.
     */
    public function uploadFavicon(
        Tenant $tenant,
        UploadedFile $file
    ): TenantProfile {
        return $this->uploadMedia(
            $tenant,
            $file,
            'favicon'
        );
    }

    /**
     * Upload or replace the director signature.
     */
    public function uploadDirectorSignature(
        Tenant $tenant,
        UploadedFile $file
    ): TenantProfile {
        return $this->uploadMedia(
            $tenant,
            $file,
            'director_signature'
        );
    }

    /**
     * Upload or replace one supported profile-media collection.
     */
    private function uploadMedia(
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
     * Remove all media from a profile collection.
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