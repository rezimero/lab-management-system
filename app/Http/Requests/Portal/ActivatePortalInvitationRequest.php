<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ActivatePortalInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'token' => trim(
                (string) $this->input('token')
            ),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => [
                'required',
                'string',
                'min:40',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'max:255',
                'confirmed',
                Password::min(12)
                    ->mixedCase()
                    ->numbers(),
            ],
            'domain' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
