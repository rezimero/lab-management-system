<?php

namespace App\Http\Requests\Department;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    /**
     * Determine whether the current user may update departments.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize supplied values without adding missing fields.
     */
    protected function prepareForValidation(): void
    {
        $prepared = [];

        if ($this->has('name')) {
            $prepared['name'] = $this->filled('name')
                ? trim((string) $this->input('name'))
                : $this->input('name');
        }

        if ($this->has('code')) {
            $prepared['code'] = $this->filled('code')
                ? strtoupper(trim((string) $this->input('code')))
                : $this->input('code');
        }

        if ($this->has('description')) {
            $prepared['description'] = $this->filled('description')
                ? trim((string) $this->input('description'))
                : null;
        }

        if ($prepared !== []) {
            $this->merge($prepared);
        }
    }

    /**
     * Get the validation rules.
     */
    public function rules(): array
    {
        $departmentUuid = $this->route('department');

        if ($departmentUuid instanceof Department) {
            $departmentUuid = $departmentUuid->uuid;
        }

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:150',
                Rule::unique(Department::class, 'name')
                    ->ignore($departmentUuid, 'uuid'),
            ],

            'code' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique(Department::class, 'code')
                    ->ignore($departmentUuid, 'uuid'),
            ],

            'description' => [
                'sometimes',
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
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Department name cannot be empty.',
            'name.unique' => 'A department with this name already exists.',
            'name.max' => 'Department name may not be greater than 150 characters.',

            'code.required' => 'Department code cannot be empty.',
            'code.unique' => 'A department with this code already exists.',
            'code.max' => 'Department code may not be greater than 30 characters.',

            'description.max' => 'Description may not be greater than 1000 characters.',

            'sort_order.integer' => 'Sort order must be a whole number.',
            'sort_order.min' => 'Sort order cannot be negative.',

            'is_active.boolean' => 'Active status must be true or false.',
        ];
    }
}