<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name')
                ? trim($this->input('name'))
                : null,

            'code' => $this->filled('code')
                ? strtoupper(trim($this->input('code')))
                : null,
        ]);
    }

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:departments,name',
            ],

            'code' => [
                'required',
                'string',
                'max:50',
                'unique:departments,code',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Department name is required.',
            'name.unique' => 'A department with this name already exists.',
            'name.max' => 'Department name may not be greater than 255 characters.',

            'code.required' => 'Department code is required.',
            'code.unique' => 'A department with this code already exists.',
            'code.max' => 'Department code may not be greater than 50 characters.',

            'description.max' => 'Description may not be greater than 1000 characters.',

            'sort_order.integer' => 'Sort order must be a whole number.',
            'sort_order.min' => 'Sort order cannot be negative.',

            'is_active.boolean' => 'Active status must be true or false.',
        ];
    }

    /**
     * Return validated data with application defaults.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated();

        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}