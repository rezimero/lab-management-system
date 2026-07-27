<?php

namespace App\Http\Requests;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantProfileRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::
                    MANAGE_TENANT_PROFILE
            );
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            */

            'lab_name' => [
                'required',
                'string',
                'max:255',
            ],

            'tagline' => [
                'nullable',
                'string',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Branding
            |--------------------------------------------------------------------------
            */

            'primary_color' => [
                'nullable',
                'regex:/^#[A-Fa-f0-9]{6}$/',
            ],

            'secondary_color' => [
                'nullable',
                'regex:/^#[A-Fa-f0-9]{6}$/',
            ],

            'accent_color' => [
                'nullable',
                'regex:/^#[A-Fa-f0-9]{6}$/',
            ],

            /*
            |--------------------------------------------------------------------------
            | Contact
            |--------------------------------------------------------------------------
            */

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:30',
            ],

            'website' => [
                'nullable',
                'url',
                'max:255',
            ],

            /*
            |--------------------------------------------------------------------------
            | Address
            |--------------------------------------------------------------------------
            */

            'address' => [
                'nullable',
                'string',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'state' => [
                'nullable',
                'string',
                'max:100',
            ],

            'country' => [
                'nullable',
                'string',
                'max:100',
            ],

            /*
            |--------------------------------------------------------------------------
            | Legal
            |--------------------------------------------------------------------------
            */

            'license_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'registration_number' => [
                'nullable',
                'string',
                'max:100',
            ],

            'tin' => [
                'nullable',
                'string',
                'max:100',
            ],

            /*
            |--------------------------------------------------------------------------
            | Reports
            |--------------------------------------------------------------------------
            */

            'report_footer' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'director_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'director_qualification' => [
                'nullable',
                'string',
                'max:255',
            ],

            'director_registration_number' => [
                'nullable',
                'string',
                'max:150',
            ],

            'signatory_designation' => [
                'nullable',
                'string',
                'max:255',
            ],

            'accreditation_details' => [
                'nullable',
                'string',
                'max:2000',
            ],

            /*
            |--------------------------------------------------------------------------
            | Invoice
            |--------------------------------------------------------------------------
            */

            'invoice_footer' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'bank_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'account_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'account_number' => [
                'nullable',
                'string',
                'max:20',
            ],
        ];
    }

    /**
     * Friendly attribute names.
     */
    public function attributes(): array
    {
        return [
            'lab_name' => 'laboratory name',
            'tin' => 'Tax Identification Number',
        ];
    }
}