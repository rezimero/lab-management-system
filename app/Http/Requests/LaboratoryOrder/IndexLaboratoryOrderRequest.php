<?php

namespace App\Http\Requests\LaboratoryOrder;

use Illuminate\Foundation\Http\FormRequest;

class IndexLaboratoryOrderRequest extends FormRequest
{
    /**
     * Tenant-authenticated users may list orders.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize searchable and enumerable inputs.
     */
    protected function prepareForValidation(): void
    {
        $payload = [];

        if ($this->has('search')) {
            $search = trim(
                (string) $this->input('search')
            );

            $payload['search'] = $search === ''
                ? null
                : $search;
        }

        if ($this->has('status')) {
            $payload['status'] = strtolower(
                trim(
                    (string) $this->input('status')
                )
            );
        }

        if ($this->has('priority')) {
            $payload['priority'] = strtolower(
                trim(
                    (string) $this->input('priority')
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
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'nullable',
                'string',
                'in:pending,sample_collected,in_progress,completed,cancelled',
            ],

            'priority' => [
                'nullable',
                'string',
                'in:routine,urgent,stat',
            ],

            'patient_id' => [
                'nullable',
                'integer',
                'min:1',
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

    /**
     * Validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.in' => (
                'The selected laboratory order status is invalid.'
            ),

            'priority.in' => (
                'Priority must be routine, urgent, or stat.'
            ),

            'date_to.after_or_equal' => (
                'The end date must be on or after the start date.'
            ),

            'per_page.max' => (
                'No more than 100 orders may be requested per page.'
            ),
        ];
    }
}
