<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class InspectPortalInvitationRequest extends FormRequest
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
     * @return array<string, array<int, string>>
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
            'domain' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }
}
