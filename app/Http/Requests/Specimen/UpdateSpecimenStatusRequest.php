<?php

namespace App\Http\Requests\Specimen;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSpecimenStatusRequest extends FormRequest
{
    /**
     * Determine whether the current user may update specimens.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize status and rejection input.
     */
    protected function prepareForValidation(): void
    {
        $status = $this->input('status');
        $reason = $this->input('rejection_reason');

        if (is_string($status)) {
            $status = strtolower(
                trim($status)
            );
        }

        if (is_string($reason)) {
            $reason = trim($reason);

            if ($reason === '') {
                $reason = null;
            }
        }

        $this->merge([
            'status' => $status,
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    'received',
                    'rejected',
                ]),
            ],

            'rejection_reason' => [
                Rule::requiredIf(
                    fn (): bool => (
                        $this->input('status')
                        === 'rejected'
                    )
                ),
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * Validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => (
                'Specimen status is required.'
            ),
            'status.in' => (
                'Specimen status must be received or rejected.'
            ),
            'rejection_reason.required' => (
                'A rejection reason is required when rejecting a specimen.'
            ),
            'rejection_reason.max' => (
                'The rejection reason may not exceed 2000 characters.'
            ),
        ];
    }
}