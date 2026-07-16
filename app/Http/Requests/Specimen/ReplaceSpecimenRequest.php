<?php

namespace App\Http\Requests\Specimen;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceSpecimenRequest extends FormRequest
{
    /**
     * Determine whether the user may replace specimens.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize optional replacement details.
     */
    protected function prepareForValidation(): void
    {
        $values = [];

        foreach (
            [
                'specimen_type',
                'container_type',
                'collection_site',
                'volume_unit',
                'collected_at',
                'notes',
            ] as $field
        ) {
            $value = $this->input($field);

            if (is_string($value)) {
                $value = trim($value);

                if ($value === '') {
                    $value = null;
                }
            }

            $values[$field] = $value;
        }

        $this->merge($values);
    }

    /**
     * Replacement validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'specimen_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'container_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'collection_site' => [
                'nullable',
                'string',
                'max:100',
            ],

            'volume' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:999999.99',
            ],

            'volume_unit' => [
                'nullable',
                'string',
                'max:20',
                'required_with:volume',
            ],

            'collected_at' => [
                'nullable',
                'date',
                'before_or_equal:now',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}
