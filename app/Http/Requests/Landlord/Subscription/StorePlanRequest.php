<?php

namespace App\Http\Requests\Landlord\Subscription;

use App\Support\Commercial\CommercialFeatureCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('features', []) as $index => $feature) {
                $key = (string) ($feature['key'] ?? '');
                $enabled = filter_var(
                    $feature['is_enabled'] ?? false,
                    FILTER_VALIDATE_BOOL
                );

                if (
                    $enabled
                    && ! in_array(
                        $key,
                        CommercialFeatureCatalog::enabledByDefault(),
                        true
                    )
                ) {
                    $validator->errors()->add(
                        "features.{$index}.is_enabled",
                        'This feature cannot be enabled until its implementation milestone is validated.'
                    );
                }
            }
        });
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:80', Rule::unique('plans', 'code')],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:5000'],
            'currency' => ['required', 'string', 'size:3'],
            'monthly_price' => [
                'required', 'numeric', 'decimal:0,2', 'min:0',
                'max:999999999999.99',
            ],
            'annual_price' => [
                'required', 'numeric', 'decimal:0,2', 'min:0',
                'max:999999999999.99',
            ],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_default' => ['sometimes', 'boolean'],
            'is_public' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'features' => ['required', 'array', 'min:1'],
            'features.*.key' => ['required', 'string', 'distinct', Rule::in(CommercialFeatureCatalog::keys())],
            'features.*.name' => ['required', 'string', 'max:180'],
            'features.*.is_enabled' => ['required', 'boolean'],
            'features.*.limit_value' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
