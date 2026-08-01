<?php

namespace App\Http\Requests\LaboratoryReportAmendment;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class ApproveLaboratoryReportAmendmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::APPROVE_LABORATORY_REPORT_AMENDMENTS
            );
    }

    /**
     * Approval has no mutable payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
