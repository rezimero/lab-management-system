<?php

namespace App\Http\Requests\Specimen;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexSpecimenRequest extends FormRequest
{
    /**
     * Determine whether the user may list specimens.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize query parameters.
     */
    protected function prepareForValidation(): void
    {
        $values = [];

        foreach (
            [
                'search',
                'status',
                'order_uuid',
                'specimen_type',
                'date_from',
                'date_to',
            ] as $field
        ) {
            $value = $this->input($field);

            if (is_string($value)) {
                $value = trim($value);

                if ($field === 'status') {
                    $value = strtolower($value);
                }

                if ($value === '') {
                    $value = null;
                }
            }

            $values[$field] = $value;
        }

        $this->merge($values);
    }

    /**
     * Query validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                'string',
                Rule::in([
                    'collected',
                    'received',
                    'rejected',
                ]),
            ],

            'order_uuid' => [
                'nullable',
                'uuid',
            ],

            'specimen_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}