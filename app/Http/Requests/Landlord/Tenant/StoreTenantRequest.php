<?php

namespace App\Http\Requests\Landlord\Tenant;

use App\Models\Tenant;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends LandlordTenantRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'domain' => [
                'required',
                'string',
                'max:255',
                Rule::unique(Tenant::class, 'domain'),
            ],

            'admin_name' => [
                'required',
                'string',
                'max:255',
            ],

            'admin_email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],

            'admin_password' => [
                'required',
                'string',
                'min:8',
            ],
        ];
    }
}