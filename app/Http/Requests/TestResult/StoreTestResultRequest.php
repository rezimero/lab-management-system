<?php

namespace App\Http\Requests\TestResult;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTestResultRequest extends FormRequest
{
    /**
     * Determine whether the user may enter results.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::CREATE_TEST_RESULTS
            );
    }

    /**
     * Validation rules for draft-result creation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'results_data' => [
                'sometimes',
                'array',
                'max:100',
            ],

            'results_data.*' => [
                'array',
            ],

            'results_data.*.name' => [
                'required',
                'string',
                'max:255',
            ],

            'results_data.*.value' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'results_data.*.unit' => [
                'nullable',
                'string',
                'max:100',
            ],

            'results_data.*.reference_range' => [
                'nullable',
                'string',
                'max:255',
            ],

            'results_data.*.flag' => [
                'nullable',
                Rule::in([
                    'normal',
                    'low',
                    'high',
                    'critical',
                    'abnormal',
                ]),
            ],

            'results_data.*.notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:10000',
            ],
        ];
    }
}
