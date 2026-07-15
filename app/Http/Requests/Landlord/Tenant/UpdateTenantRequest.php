<?php

namespace App\Http\Requests\Landlord\Tenant;

use App\Models\Tenant;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends LandlordTenantRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],

            'domain' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique(Tenant::class, 'domain')
                    ->ignore($this->route('id')),
            ],

            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}