<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class IndexNotificationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('unread')) {
            $this->merge([
                'unread' => filter_var(
                    $this->input('unread'),
                    FILTER_VALIDATE_BOOL,
                    FILTER_NULL_ON_FAILURE
                ),
            ]);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'unread' => ['sometimes', 'boolean'],
            'category' => [
                'sometimes',
                'nullable',
                'string',
                'max:60',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:50',
            ],
        ];
    }
}
