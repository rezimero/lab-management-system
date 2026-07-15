<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadLogoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'logo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,svg,webp',
                'max:2048', // 2MB
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'logo.required' => 'Please select a logo to upload.',
            'logo.image' => 'The uploaded file must be an image.',
            'logo.mimes' => 'Only JPG, JPEG, PNG, SVG and WEBP images are allowed.',
            'logo.max' => 'The logo may not be larger than 2 MB.',
        ];
    }

    /**
     * Friendly attribute names.
     */
    public function attributes(): array
    {
        return [
            'logo' => 'laboratory logo',
        ];
    }
}