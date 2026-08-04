<?php

namespace App\Http\Requests\Referral;

use App\Models\ReferralLaboratory;
use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReferralLaboratoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::MANAGE_REFERRAL_LABORATORIES
            );
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim((string) $this->input('code'))),
            'name' => trim((string) $this->input('name')),
            'currency' => strtoupper(trim(
                (string) ($this->input('currency') ?: 'NGN')
            )),
            'country' => trim(
                (string) ($this->input('country') ?: 'Nigeria')
            ),
        ]);
    }

    public function rules(): array
    {
        $laboratory = $this->route('laboratory');
        $id = $laboratory instanceof ReferralLaboratory
            ? $laboratory->getKey()
            : null;

        return [
            'code' => [
                'required',
                'string',
                'max:60',
                Rule::unique('referral_laboratories', 'code')->ignore($id),
            ],
            'name' => ['required', 'string', 'max:180'],
            'accreditation_number' => ['nullable', 'string', 'max:120'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:120'],
            'currency' => ['required', 'string', 'size:3', 'alpha'],
            'default_turnaround_hours' => [
                'nullable',
                'integer',
                'min:1',
                'max:87600',
            ],
            'is_active' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
