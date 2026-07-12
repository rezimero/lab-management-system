<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
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
        if ($this->filled('name')) {
            $this->merge([
                'name' => trim($this->input('name')),
            ]);
        }

        if ($this->filled('code')) {
            $this->merge([
                'code' => strtoupper(trim($this->input('code'))),
            ]);
        }
    }

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        $department = $this->route('department');
        $departmentId = $department?->getKey();

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($departmentId),
            ],

            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'code')->ignore($departmentId),
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'sort_order' => [
                'sometimes',
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
}