<?php

namespace App\Http\Requests\Landlord\Tenant;

class ResetTenantAdminPasswordRequest extends LandlordTenantRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
            ],

            'new_password' => [
                'required',
                'string',
                'min:8',
            ],
        ];
    }
}