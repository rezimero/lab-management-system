<?php

namespace App\Http\Requests\LaboratoryReport;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class ViewLaboratoryReportRequest extends FormRequest
{
    /**
     * Determine whether the user may view reports.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::
                    VIEW_LABORATORY_REPORTS
            );
    }

    /**
     * Report retrieval has no request payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
