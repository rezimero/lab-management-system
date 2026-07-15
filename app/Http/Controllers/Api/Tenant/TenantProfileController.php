<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTenantProfileRequest;
use App\Http\Requests\UploadLogoRequest;
use App\Services\TenantProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantProfileController extends Controller
{
    public function __construct(
        protected TenantProfileService $profileService
    ) {
    }

    /**
     * Get the current laboratory profile.
     */
    public function show(Request $request): JsonResponse
    {
        $profile = $this->profileService->getProfile(
            tenant()
        );

        return response()->json([
            'success' => true,
            'data' => $profile,
        ]);
    }

    /**
     * Update laboratory profile.
     */
    public function update(UpdateTenantProfileRequest $request): JsonResponse
    {
        $profile = $this->profileService->updateProfile(
            tenant(),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Laboratory profile updated successfully.',
            'data' => $profile,
        ]);
    }

    /**
     * Upload laboratory logo.
     */
    public function uploadLogo(
        UploadLogoRequest $request
    ): JsonResponse {

        $profile = $this->profileService->uploadLogo(
            tenant(),
            $request->file('file')
        );

        return response()->json([
            'success' => true,
            'message' => 'Laboratory logo uploaded successfully.',
            'data' => $profile,
        ]);
    }

    /**
     * Upload laboratory favicon.
     */
    public function uploadFavicon(
        UploadLogoRequest $request
    ): JsonResponse {

        $profile = $this->profileService->uploadFavicon(
            tenant(),
            $request->file('file')
        );

        return response()->json([
            'success' => true,
            'message' => 'Laboratory favicon uploaded successfully.',
            'data' => $profile,
        ]);
    }

    /**
     * Upload laboratory director signature.
     */
    public function uploadDirectorSignature(
        UploadLogoRequest $request
    ): JsonResponse {

        $profile = $this->profileService->uploadDirectorSignature(
            tenant(),
            $request->file('file')
        );

        return response()->json([
            'success' => true,
            'message' => 'Director signature uploaded successfully.',
            'data' => $profile,
        ]);
    }
}