<?php

namespace App\Http\Requests\Department;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    /**
     * Determine whether the current user may create departments.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize input and provide application defaults.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name')
                ? trim((string) $this->input('name'))
                : $this->input('name'),

            'code' => $this->filled('code')
                ? strtoupper(trim((string) $this->input('code')))
                : $this->input('code'),

            'description' => $this->filled('description')
                ? trim((string) $this->input('description'))
                : $this->input('description'),

            'sort_order' => $this->has('sort_order')
                ? $this->input('sort_order')
                : 0,

            'is_active' => $this->has('is_active')
                ? $this->input('is_active')
                : true,
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
            'name' => [
                'required',
                'string',
                'max:150',
                Rule::unique(Department::class, 'name'),
            ],

            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique(Department::class, 'code'),
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Department name is required.',
            'name.unique' => 'A department with this name already exists.',
            'name.max' => 'Department name may not be greater than 150 characters.',

            'code.required' => 'Department code is required.',
            'code.unique' => 'A department with this code already exists.',
            'code.max' => 'Department code may not be greater than 30 characters.',

            'description.max' => 'Description may not be greater than 1000 characters.',

            'sort_order.required' => 'Sort order is required.',
            'sort_order.integer' => 'Sort order must be a whole number.',
            'sort_order.min' => 'Sort order cannot be negative.',

            'is_active.required' => 'Active status is required.',
            'is_active.boolean' => 'Active status must be true or false.',
        ];
    }
}