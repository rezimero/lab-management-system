<?php

namespace App\Http\Requests\Landlord\Subscription;

use App\Models\Plan;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends StorePlanRequest
{
    public function rules(): array
    {
        $plan = $this->route('plan');
        $id = $plan instanceof Plan ? $plan->getKey() : null;
        $rules = parent::rules();
        $rules['code'] = [
            'required', 'string', 'max:80',
            Rule::unique('plans', 'code')->ignore($id),
        ];

        return $rules;
    }
}
