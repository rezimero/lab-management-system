<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\User\AssignTenantUserRoleRequest;
use App\Http\Requests\Tenant\User\StoreTenantUserRequest;
use App\Http\Requests\Tenant\User\UpdateTenantUserRequest;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\User\TenantAdministratorGuard;
use App\Services\Tenant\User\TenantUserAccountService;
use App\Services\Tenant\User\TenantUserQueryService;
use App\Support\Tenant\TenantUserRoleCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected TenantUserQueryService $queries,
        protected TenantUserAccountService $accounts,
        protected TenantAdministratorGuard $guard,
        protected TenantUserRoleCatalog $roles,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $tenant = $this->currentTenant();
        $currentUser = $this->authenticatedUser($request);

        $this->guard->ensureCanManage($currentUser);

        $users = $this->queries->paginate(
            tenant: $tenant,
            search: $request->input('search'),
            perPage: (int) $request->input('per_page', 15),
            page: (int) $request->input('page', 1),
        );

        return response()->json([
            'users' => $users,
            'available_roles' => $this->roles
                ->availableFor($currentUser->role),
            'current_user_role' => $currentUser->role,
            'is_superadmin' => false,
            'tenant_name' => $tenant->name,
            'user_email' => $currentUser->email,
            'user_id' => $currentUser->getKey(),
        ]);
    }

    public function show(
        Request $request,
        int|string $user
    ): JsonResponse {
        $currentUser = $this->authenticatedUser($request);
        $target = $this->queries->find($user);

        if (! $target) {
            return $this->notFound();
        }

        $this->guard->ensureCanView(
            $currentUser,
            $target
        );

        return response()->json($target);
    }

    public function store(
        StoreTenantUserRequest $request
    ): JsonResponse {
        $tenant = $this->currentTenant();
        $currentUser = $this->authenticatedUser($request);

        $user = $this->accounts->create(
            $tenant,
            $currentUser,
            $request->validated()
        );

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'tenant' => [
                'id' => $tenant->getKey(),
                'name' => $tenant->name,
                'domain' => $tenant->domain,
            ],
        ], 201);
    }

    public function update(
        UpdateTenantUserRequest $request,
        int|string $user
    ): JsonResponse {
        $tenant = $this->currentTenant();
        $currentUser = $this->authenticatedUser($request);
        $target = $this->queries->find($user);

        if (
            ! $target
            || $target->role === 'superadmin'
        ) {
            return $this->notFound();
        }

        $result = $this->accounts->update(
            $request,
            $tenant,
            $currentUser,
            $target,
            $request->validated()
        );

        return response()->json([
            'message' => $result['self_service']
                ? 'Your account was updated successfully'
                : 'User updated successfully',
            'user' => $result['user'],
            'self_service' => $result['self_service'],
            'sessions_revoked' => $result['sessions_revoked'],
        ]);
    }

    public function destroy(
        Request $request,
        int|string $user
    ): JsonResponse {
        $tenant = $this->currentTenant();
        $currentUser = $this->authenticatedUser($request);
        $target = $this->queries->find($user);

        if (
            ! $target
            || $target->role === 'superadmin'
        ) {
            return $this->notFound();
        }

        $this->accounts->delete(
            $tenant,
            $currentUser,
            $target
        );

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }

    public function assignRole(
        AssignTenantUserRoleRequest $request,
        int|string $user
    ): JsonResponse {
        $tenant = $this->currentTenant();
        $currentUser = $this->authenticatedUser($request);
        $target = $this->queries->find($user);

        if (
            ! $target
            || $target->role === 'superadmin'
        ) {
            return $this->notFound();
        }

        $result = $this->accounts->assignRole(
            $tenant,
            $currentUser,
            $target,
            $request->validated('role')
        );

        return response()->json([
            'message' => 'Role assigned successfully',
            'user' => $result['user'],
            'sessions_revoked' => $result['sessions_revoked'],
        ]);
    }

    public function getRoles(): JsonResponse
    {
        return response()->json([
            'roles' => $this->roles->tenantRoles(),
        ]);
    }

    private function authenticatedUser(
        Request $request
    ): TenantUser {
        $user = $request->user();

        if (! $user instanceof TenantUser) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }

    private function currentTenant(): Tenant
    {
        $tenant = Tenant::current();

        if (! $tenant) {
            abort(
                422,
                'Tenant context is required.'
            );
        }

        return $tenant;
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message' => 'User not found',
        ], 404);
    }
}