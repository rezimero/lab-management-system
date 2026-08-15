<?php

namespace App\Http\Requests\Portal;

use App\Models\PortalInvitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestPortalPasswordResetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'account_type' => strtolower(trim(
                (string) $this->input('account_type')
            )),
            'email' => strtolower(trim(
                (string) $this->input('email')
            )),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'account_type' => [
                'required',
                Rule::in([
                    PortalInvitation::ACCOUNT_PATIENT,
                    PortalInvitation::ACCOUNT_CLINICIAN,
                ]),
            ],
            'email' => [
                'required',
                'string',
                'email',
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
