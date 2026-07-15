<?php

namespace App\Http\Requests\LaboratoryOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLaboratoryOrderStatusRequest extends FormRequest
{
    /**
     * Tenant-authenticated users may update order status.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the submitted status.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('status')) {
            return;
        }

        $this->merge([
            'status' => strtolower(
                trim(
                    (string) $this->input('status')
                )
            ),
        ]);
    }

    /**
     * Validation rules.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                'in:pending,sample_collected,in_progress,completed,cancelled',
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
                'A laboratory order status is required.'
            ),

            'status.in' => (
                'The selected laboratory order status is invalid.'
            ),
        ];
    }
}
