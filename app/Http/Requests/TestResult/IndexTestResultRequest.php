<?php

namespace App\Http\Requests\TestResult;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTestResultRequest extends FormRequest
{
    /**
     * Determine whether the user may view results.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::VIEW_TEST_RESULTS
            );
    }

    /**
     * Normalize query parameters.
     */
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach ([
            'search',
            'status',
            'order_uuid',
            'order_item_uuid',
            'from_date',
            'to_date',
            'sort',
            'direction',
        ] as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $value = $this->input($field);

            $normalized[$field] = is_string($value)
                ? trim($value)
                : $value;
        }

        $this->merge($normalized);
    }

    /**
     * Validation rules for result queries.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'draft',
                    'submitted',
                    'approved',
                    'rejected',
                ]),
            ],

            'order_uuid' => [
                'nullable',
                'uuid',
            ],

            'order_item_uuid' => [
                'nullable',
                'uuid',
            ],

            'patient_id' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'from_date' => [
                'nullable',
                'date',
            ],

            'to_date' => [
                'nullable',
                'date',
                'after_or_equal:from_date',
            ],

            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'between:1,100',
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'created_at',
                    'updated_at',
                    'submitted_at',
                    'reviewed_at',
                    'status',
                ]),
            ],

            'direction' => [
                'nullable',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],
        ];
    }
}
