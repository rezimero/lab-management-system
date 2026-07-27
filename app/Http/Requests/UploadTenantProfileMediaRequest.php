<?php

namespace App\Http\Requests;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class UploadTenantProfileMediaRequest extends FormRequest
{
    /**
     * Only active tenant administrators may upload profile media.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::
                    MANAGE_TENANT_PROFILE
            );
    }

    /**
     * Validate safe image uploads.
     *
     * SVG is intentionally excluded because it may contain active content.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => (
                'Please select an image to upload.'
            ),
            'file.image' => (
                'The uploaded file must be an image.'
            ),
            'file.mimes' => (
                'Only JPG, JPEG, PNG and WEBP images are allowed.'
            ),
            'file.max' => (
                'The image may not be larger than 2 MB.'
            ),
        ];
    }

    /**
     * Friendly attribute names.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'file' => 'profile image',
        ];
    }
}