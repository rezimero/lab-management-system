<?php

namespace App\Http\Requests\Analyzer;

use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Foundation\Http\FormRequest;

class RejectAnalyzerImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::REVIEW_ANALYZER_IMPORTS
            );
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('note')) {
            $this->merge([
                'note' => trim((string) $this->input('note')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'note' => [
                'required',
                'string',
                'min:3',
                'max:2000',
            ],
        ];
    }
}
