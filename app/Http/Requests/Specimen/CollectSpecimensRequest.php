<?php

namespace App\Http\Requests\Specimen;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CollectSpecimensRequest extends FormRequest
{
    /**
     * Tenant-authenticated users may collect specimens.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'specimens' => [
                'required',
                'array',
                'min:1',
                'max:20',
            ],

            'specimens.*.specimen_type' => [
                'required',
                'string',
                'max:100',
            ],

            'specimens.*.container_type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'specimens.*.collection_site' => [
                'nullable',
                'string',
                'max:100',
            ],

            'specimens.*.volume' => [
                'nullable',
                'numeric',
                'gt:0',
                'max:999999.99',
            ],

            'specimens.*.volume_unit' => [
                'nullable',
                'required_with:specimens.*.volume',
                'string',
                'max:20',
            ],

            'specimens.*.collected_at' => [
                'nullable',
                'date',
            ],

            'specimens.*.notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'specimens.*.order_item_uuids' => [
                'required',
                'array',
                'min:1',
            ],

            'specimens.*.order_item_uuids.*' => [
                'required',
                'uuid',
            ],
        ];
    }

    /**
     * Perform specimen-specific validation.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $specimens = $this->input(
                    'specimens',
                    []
                );

                if (! is_array($specimens)) {
                    return;
                }

                foreach (
                    $specimens as $index => $specimen
                ) {
                    if (! is_array($specimen)) {
                        continue;
                    }

                    $itemUuids = (
                        $specimen['order_item_uuids']
                        ?? null
                    );

                    if (! is_array($itemUuids)) {
                        continue;
                    }

                    $normalizedUuids = array_map(
                        static fn (mixed $uuid): string => (
                            (string) $uuid
                        ),
                        $itemUuids
                    );

                    if (
                        count($normalizedUuids)
                        === count(
                            array_unique(
                                $normalizedUuids
                            )
                        )
                    ) {
                        continue;
                    }

                    $validator->errors()->add(
                        "specimens.{$index}.order_item_uuids",
                        'An order item cannot be repeated within one specimen.'
                    );
                }
            },
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
            'specimens.required' => (
                'At least one specimen is required.'
            ),

            'specimens.*.specimen_type.required' => (
                'Each specimen must have a specimen type.'
            ),

            'specimens.*.volume.gt' => (
                'Specimen volume must be greater than zero.'
            ),

            'specimens.*.volume_unit.required_with' => (
                'A volume unit is required when volume is provided.'
            ),

            'specimens.*.order_item_uuids.required' => (
                'Each specimen must be linked to at least one order item.'
            ),

            'specimens.*.order_item_uuids.*.uuid' => (
                'Each selected order item must use a valid UUID.'
            ),
        ];
    }
}
