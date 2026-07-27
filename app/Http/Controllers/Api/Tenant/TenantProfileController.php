<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTenantProfileRequest;
use App\Http\Requests\UploadTenantProfileMediaRequest;
use App\Http\Requests\ViewTenantProfileRequest;
use App\Http\Resources\TenantProfileResource;
use App\Models\Tenant;
use App\Models\TenantProfile;
use App\Services\TenantProfileService;
use Illuminate\Http\JsonResponse;
use LogicException;

class TenantProfileController extends Controller
{
    public function __construct(
        private readonly TenantProfileService $profileService
    ) {
    }

    /**
     * Get the current laboratory profile.
     */
    public function show(
        ViewTenantProfileRequest $request
    ): JsonResponse {
        return $this->profileResponse(
            $this->profileService->getProfile(
                $this->currentTenant()
            )
        );
    }

    /**
     * Update laboratory profile.
     */
    public function update(
        UpdateTenantProfileRequest $request
    ): JsonResponse {
        return $this->profileResponse(
            $this->profileService->updateProfile(
                $this->currentTenant(),
                $request->validated()
            ),
            'Laboratory profile updated successfully.'
        );
    }

    /**
     * Upload laboratory logo.
     */
    public function uploadLogo(
        UploadTenantProfileMediaRequest $request
    ): JsonResponse {
        return $this->profileResponse(
            $this->profileService->uploadLogo(
                $this->currentTenant(),
                $request->file('file')
            ),
            'Laboratory logo uploaded successfully.'
        );
    }

    /**
     * Upload laboratory favicon.
     */
    public function uploadFavicon(
        UploadTenantProfileMediaRequest $request
    ): JsonResponse {
        return $this->profileResponse(
            $this->profileService->uploadFavicon(
                $this->currentTenant(),
                $request->file('file')
            ),
            'Laboratory favicon uploaded successfully.'
        );
    }

    /**
     * Upload laboratory director signature.
     */
    public function uploadDirectorSignature(
        UploadTenantProfileMediaRequest $request
    ): JsonResponse {
        return $this->profileResponse(
            $this->profileService
                ->uploadDirectorSignature(
                    $this->currentTenant(),
                    $request->file('file')
                ),
            'Director signature uploaded successfully.'
        );
    }

    /**
     * Resolve the tenant selected by SetTenantContext.
     */
    private function currentTenant(): Tenant
    {
        $tenant = Tenant::current();

        if (! $tenant instanceof Tenant) {
            throw new LogicException(
                'No active tenant is available.'
            );
        }

        return $tenant;
    }

    /**
     * Serialize the administrator-only profile contract.
     */
    private function profileResponse(
        TenantProfile $profile,
        ?string $message = null
    ): JsonResponse {
        $additional = [
            'success' => true,
        ];

        if ($message !== null) {
            $additional['message'] = $message;
        }

        return (
            new TenantProfileResource(
                $profile
            )
        )
            ->additional($additional)
            ->response()
            ->setStatusCode(200);
    }
}