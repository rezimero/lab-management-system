<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\User\AssignTenantUserRoleRequest;
use App\Http\Requests\Tenant\User\StoreTenantUserRequest;
use App\Http\Requests\Tenant\User\UpdateTenantUserRequest;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\User\TenantUserQueryService;
use App\Services\Tenant\User\TenantUserService;
use App\Support\Tenant\TenantUserRoleCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserController extends Controller
{
    public function __construct(
        protected TenantUserQueryService $queryService,
        protected TenantUserService $userService,
        protected TenantUserRoleCatalog $roleCatalog,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $tenant = Tenant::current();

            if (! $tenant) {
                return response()->json([
                    'users' => ['data' => [], 'total' => 0],
                    'current_user_role' => 'guest',
                    'is_superadmin' => false,
                    'tenant_name' => null,
                ]);
            }

            $currentUser = $request->user();

            if (! $currentUser instanceof TenantUser) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            $users = $this->queryService->paginate(
                tenant: $tenant,
                search: $request->input('search'),
                perPage: (int) $request->input('per_page', 15),
                page: (int) $request->input('page', 1),
            );

            return response()->json([
                'users' => $users,
                'available_roles' => $this->roleCatalog
                    ->availableFor($currentUser->role),
                'current_user_role' => $currentUser->role,
                'is_superadmin' => $currentUser->role === 'superadmin',
                'tenant_name' => $tenant->name,
                'user_email' => $currentUser->email,
                'user_id' => $currentUser->id,
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to list tenant users.', [
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'users' => ['data' => [], 'total' => 0],
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function show(int|string $user): JsonResponse
    {
        $target = $this->queryService->find($user);

        if (! $target) {
            return $this->notFound();
        }

        return response()->json($target);
    }

    public function store(
        StoreTenantUserRequest $request
    ): JsonResponse {
        try {
            $data = $request->validated();

            if ($data['role'] === 'superadmin') {
                return response()->json([
                    'message' => 'Unauthorized to create superadmin user',
                ], 403);
            }

            $tenant = Tenant::current();

            if (! $tenant) {
                return response()->json([
                    'message' => 'No tenant context',
                ], 400);
            }

            $user = $this->userService->create($data);

            return response()->json([
                'message' => 'User created successfully',
                'user' => $user,
                'tenant' => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
            ], 201);
        } catch (Throwable $exception) {
            Log::error('Failed to create tenant user.', [
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to create user: '
                    .$exception->getMessage(),
            ], 500);
        }
    }

    public function update(
        UpdateTenantUserRequest $request,
        int|string $user
    ): JsonResponse {
        try {
            $target = $this->queryService->find($user);

            if (! $target || $target->role === 'superadmin') {
                return $this->notFound();
            }

            $data = $request->validated();

            if (($data['role'] ?? null) === 'superadmin') {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'message' => 'User updated successfully',
                'user' => $this->userService->update(
                    $target,
                    $data
                ),
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to update tenant user.', [
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to update user',
            ], 500);
        }
    }

    public function destroy(
        Request $request,
        int|string $user
    ): JsonResponse {
        try {
            $target = $this->queryService->find($user);

            if (! $target || $target->role === 'superadmin') {
                return $this->notFound();
            }

            if ((int) $request->user()->id === (int) $target->id) {
                return response()->json([
                    'message' => 'Cannot delete your own account',
                ], 422);
            }

            $this->userService->delete($target);

            return response()->json([
                'message' => 'User deleted successfully',
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to delete tenant user.', [
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to delete user',
            ], 500);
        }
    }

    public function assignRole(
        AssignTenantUserRoleRequest $request,
        int|string $user
    ): JsonResponse {
        try {
            $target = $this->queryService->find($user);

            if (! $target || $target->role === 'superadmin') {
                return $this->notFound();
            }

            $role = $request->validated('role');

            if ($role === 'superadmin') {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'message' => 'Role assigned successfully',
                'user' => $this->userService->assignRole(
                    $target,
                    $role
                ),
            ]);
        } catch (Throwable $exception) {
            Log::error('Failed to assign tenant-user role.', [
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to assign role',
            ], 500);
        }
    }

    public function getRoles(): JsonResponse
    {
        return response()->json([
            'roles' => $this->roleCatalog->tenantRoles(),
        ]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message' => 'User not found',
        ], 404);
    }
}