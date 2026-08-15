<?php

namespace App\Http\Requests\Notification;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'category' => [
                'required',
                'string',
                'max:60',
            ],
            'in_app_enabled' => [
                'required',
                'boolean',
            ],
            'email_enabled' => [
                'required',
                'boolean',
            ],
        ];
    }
}
