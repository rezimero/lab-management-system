<?php

namespace App\Http\Requests\Landlord\Subscription;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignTenantSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_uuid' => ['required', 'uuid', Rule::exists('plans', 'uuid')],
            'status' => ['required', 'string', 'in:trialing,active,past_due,suspended,cancelled,expired'],
            'billing_interval' => ['required', 'string', 'in:monthly,annual'],
            'starts_at' => ['nullable', 'date'],
            'trial_ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'current_period_ends_at' => [
                'nullable', 'date', 'after_or_equal:starts_at',
            ],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
