<?php

namespace App\Http\Requests\Portal;

use Illuminate\Foundation\Http\FormRequest;

class StoreReferringClinicianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => $this->nullableText('email', true),
            'phone' => $this->nullableText('phone'),
            'organisation' => $this->nullableText('organisation'),
            'registration_number' => $this->nullableText(
                'registration_number'
            ),
            'specialty' => $this->nullableText('specialty'),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:180',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:40',
            ],
            'organisation' => [
                'nullable',
                'string',
                'max:180',
            ],
            'registration_number' => [
                'nullable',
                'string',
                'max:120',
            ],
            'specialty' => [
                'nullable',
                'string',
                'max:120',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    private function nullableText(
        string $key,
        bool $lowercase = false
    ): ?string {
        $value = trim((string) $this->input($key, ''));

        if ($value === '') {
            return null;
        }

        return $lowercase
            ? strtolower($value)
            : $value;
    }
}
