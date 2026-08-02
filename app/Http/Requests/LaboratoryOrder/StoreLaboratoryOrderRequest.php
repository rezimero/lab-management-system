<?php

namespace App\Http\Requests\LaboratoryOrder;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaboratoryOrderRequest extends FormRequest
{
    /**
     * Tenant-authenticated users may create orders.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize optional text and defaults.
     */
    protected function prepareForValidation(): void
    {
        $payload = [
            'priority' => $this->has('priority')
                ? strtolower(
                    trim(
                        (string) $this->input(
                            'priority'
                        )
                    )
                )
                : 'routine',
        ];

        if ($this->has('referring_clinician')) {
            $payload['referring_clinician'] = (
                $this->normalizeNullableText(
                    'referring_clinician'
                )
            );
        }

        if ($this->has('clinical_notes')) {
            $payload['clinical_notes'] = (
                $this->normalizeNullableText(
                    'clinical_notes'
                )
            );
        }

        $this->merge($payload);
    }

    /**
     * Validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'patient_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'collection_branch_uuid' => [
                'nullable',
                'uuid',
                'exists:branches,uuid',
            ],

            'processing_branch_uuid' => [
                'nullable',
                'uuid',
                'exists:branches,uuid',
            ],

            'price_list_uuid' => [
                'nullable',
                'uuid',
                'exists:price_lists,uuid',
            ],

            'referring_clinician' => [
                'nullable',
                'string',
                'max:255',
            ],

            'priority' => [
                'required',
                'string',
                'in:routine,urgent,stat',
            ],

            'clinical_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],

            'items.*.lab_test_id' => [
                'required',
                'integer',
                'min:1',
                'distinct',
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
            'patient_id.required' => (
                'A patient is required.'
            ),

            'priority.in' => (
                'Priority must be routine, urgent, or stat.'
            ),

            'items.required' => (
                'At least one laboratory test is required.'
            ),

            'items.min' => (
                'At least one laboratory test is required.'
            ),

            'items.*.lab_test_id.required' => (
                'Each order item must contain a laboratory test.'
            ),

            'items.*.lab_test_id.distinct' => (
                'A laboratory test cannot be added more than once.'
            ),
        ];
    }

    /**
     * Normalize a nullable text field.
     */
    private function normalizeNullableText(
        string $key
    ): ?string {
        $value = $this->input($key);

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }
}
