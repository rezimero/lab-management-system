<?php

namespace App\Http\Requests\TestResult;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class RejectTestResultRequest extends FormRequest
{
    /**
     * Determine whether the user may reject results.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::APPROVE_TEST_RESULTS
            );
    }

    /**
     * Normalize the rejection reason.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('rejection_reason')) {
            return;
        }

        $reason = $this->input(
            'rejection_reason'
        );

        $this->merge([
            'rejection_reason' => (
                is_string($reason)
                    ? trim($reason)
                    : $reason
            ),
        ]);
    }

    /**
     * Validation rules for rejecting a result.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rejection_reason' => [
                'required',
                'string',
                'max:5000',
            ],
        ];
    }
}
