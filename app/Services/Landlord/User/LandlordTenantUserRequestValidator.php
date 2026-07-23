<?php

namespace App\Services\Landlord\User;

use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Support\Tenant\TenantUserRoleCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class LandlordTenantUserRequestValidator
{
    public function __construct(
        protected TenantUserRoleCatalog $roleCatalog
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function index(Request $request): array
    {
        return $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'tenant_id' => [
                'nullable',
                'integer',
                'exists:landlord.tenants,id',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);
    }

    public function tenant(Request $request): Tenant
    {
        $validated = $request->validate([
            'tenant_id' => [
                'required',
                'integer',
                'exists:landlord.tenants,id',
            ],
        ]);

        return Tenant::query()->findOrFail(
            $validated['tenant_id']
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function create(Request $request): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('tenant.users', 'email'),
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                $this->strongPassword(),
            ],
            'role' => [
                'required',
                'string',
                Rule::in($this->roleKeys()),
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
            'email_verified' => [
                'sometimes',
                'boolean',
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(
        Request $request,
        TenantUser $user
    ): array {
        return $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(
                    'tenant.users',
                    'email'
                )->ignore($user->getKey()),
            ],
            'password' => [
                'sometimes',
                'nullable',
                'string',
                'confirmed',
                $this->strongPassword(),
            ],
            'role' => [
                'sometimes',
                'required',
                'string',
                Rule::in($this->roleKeys()),
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
            'email_verified' => [
                'sometimes',
                'boolean',
            ],
        ]);
    }

    /**
     * @return array{password: string}
     */
    public function password(Request $request): array
    {
        return $request->validate([
            'password' => [
                'required',
                'string',
                'confirmed',
                $this->strongPassword(),
            ],
        ]);
    }

    /**
     * @return array{role: string}
     */
    public function role(Request $request): array
    {
        return $request->validate([
            'role' => [
                'required',
                'string',
                Rule::in($this->roleKeys()),
            ],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function roleKeys(): array
    {
        return array_keys(
            $this->roleCatalog->tenantRoles()
        );
    }

    private function strongPassword(): Password
    {
        return Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols();
    }
}
