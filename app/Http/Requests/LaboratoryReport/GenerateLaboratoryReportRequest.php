<?php

namespace App\Http\Requests\LaboratoryReport;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class GenerateLaboratoryReportRequest extends FormRequest
{
    /**
     * Determine whether the user may issue reports.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::
                    GENERATE_LABORATORY_REPORTS
            );
    }

    /**
     * Report generation has no request payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
