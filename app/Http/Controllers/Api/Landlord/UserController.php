<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Services\Landlord\TenantContextRunner;
use App\Services\Landlord\User\LandlordTenantUserAccessService;
use App\Services\Landlord\User\LandlordTenantUserPresenter;
use App\Services\Landlord\User\LandlordTenantUserProfileService;
use App\Services\Landlord\User\LandlordTenantUserQueryService;
use App\Services\Landlord\User\LandlordTenantUserRequestValidator;
use App\Support\Tenant\TenantUserRoleCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected TenantUserRoleCatalog $roleCatalog,
        protected TenantContextRunner $contextRunner,
        protected LandlordTenantUserRequestValidator $validator,
        protected LandlordTenantUserQueryService $queryService,
        protected LandlordTenantUserProfileService $profileService,
        protected LandlordTenantUserAccessService $accessService,
        protected LandlordTenantUserPresenter $presenter
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->queryService->paginate(
            $this->validator->index($request)
        );

        return response()->json([
            'users' => $result['users'],
            'roles' => $this->roleCatalog->tenantRoles(),
            'total_tenants' => $result['total_tenants'],
        ]);
    }

    public function show(
        Request $request,
        int|string $id
    ): JsonResponse {
        $tenant = $this->validator->tenant($request);

        return $this->contextRunner->run(
            $tenant,
            function () use ($tenant, $id): JsonResponse {
                $user = $this->queryService
                    ->findInCurrentTenant($id);

                if ($user === null) {
                    return $this->notFound();
                }

                return response()->json([
                    'user' => $this->presenter->present(
                        $tenant,
                        $user
                    ),
                    'roles' =>
                        $this->roleCatalog->tenantRoles(),
                ]);
            }
        );
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $this->validator->tenant($request);
        $actorId = $this->actorId($request);

        return $this->contextRunner->run(
            $tenant,
            function () use (
                $request,
                $tenant,
                $actorId
            ): JsonResponse {
                $user = $this->profileService->create(
                    tenant: $tenant,
                    data: $this->validator->create($request),
                    actorId: $actorId
                );

                return response()->json([
                    'message' =>
                        'User created successfully.',
                    'user' => $this->presenter->present(
                        $tenant,
                        $user
                    ),
                ], 201);
            }
        );
    }

    public function update(
        Request $request,
        int|string $id
    ): JsonResponse {
        $tenant = $this->validator->tenant($request);
        $actorId = $this->actorId($request);

        return $this->contextRunner->run(
            $tenant,
            function () use (
                $request,
                $tenant,
                $id,
                $actorId
            ): JsonResponse {
                $user = $this->queryService
                    ->findInCurrentTenant($id);

                if ($user === null) {
                    return $this->notFound();
                }

                $result = $this->profileService->update(
                    tenant: $tenant,
                    user: $user,
                    data: $this->validator->update(
                        $request,
                        $user
                    ),
                    actorId: $actorId
                );

                return response()->json([
                    'message' =>
                        'User updated successfully.',
                    'user' => $this->presenter->present(
                        $tenant,
                        $result['user']
                    ),
                    'sessions_revoked' =>
                        $result['sessions_revoked'],
                ]);
            }
        );
    }

    public function destroy(
        Request $request,
        int|string $id
    ): JsonResponse {
        $tenant = $this->validator->tenant($request);
        $actorId = $this->actorId($request);

        return $this->contextRunner->run(
            $tenant,
            function () use (
                $tenant,
                $id,
                $actorId
            ): JsonResponse {
                $user = $this->queryService
                    ->findInCurrentTenant($id);

                if ($user === null) {
                    return $this->notFound();
                }

                $snapshot = $this->presenter->present(
                    $tenant,
                    $user
                );

                $this->accessService->delete(
                    tenant: $tenant,
                    user: $user,
                    actorId: $actorId
                );

                return response()->json([
                    'message' =>
                        'User deleted successfully.',
                    'user' => $snapshot,
                ]);
            }
        );
    }

    public function resetPassword(
        Request $request,
        int|string $id
    ): JsonResponse {
        $tenant = $this->validator->tenant($request);
        $actorId = $this->actorId($request);

        return $this->contextRunner->run(
            $tenant,
            function () use (
                $request,
                $tenant,
                $id,
                $actorId
            ): JsonResponse {
                $user = $this->queryService
                    ->findInCurrentTenant($id);

                if ($user === null) {
                    return $this->notFound();
                }

                $validated = $this->validator
                    ->password($request);

                $updatedUser = $this->accessService
                    ->resetPassword(
                        tenant: $tenant,
                        user: $user,
                        password: $validated['password'],
                        actorId: $actorId
                    );

                return response()->json([
                    'message' =>
                        'Password reset successfully. Existing sessions were revoked.',
                    'user' => $this->presenter->present(
                        $tenant,
                        $updatedUser
                    ),
                ]);
            }
        );
    }

    public function assignRole(
        Request $request,
        int|string $id
    ): JsonResponse {
        $tenant = $this->validator->tenant($request);
        $actorId = $this->actorId($request);

        return $this->contextRunner->run(
            $tenant,
            function () use (
                $request,
                $tenant,
                $id,
                $actorId
            ): JsonResponse {
                $user = $this->queryService
                    ->findInCurrentTenant($id);

                if ($user === null) {
                    return $this->notFound();
                }

                $validated = $this->validator
                    ->role($request);

                $result = $this->accessService
                    ->assignRole(
                        tenant: $tenant,
                        user: $user,
                        role: $validated['role'],
                        actorId: $actorId
                    );

                return response()->json([
                    'message' =>
                        'Role assigned successfully.',
                    'user' => $this->presenter->present(
                        $tenant,
                        $result['user']
                    ),
                    'sessions_revoked' =>
                        $result['sessions_revoked'],
                ]);
            }
        );
    }

    public function getRoles(): JsonResponse
    {
        return response()->json([
            'roles' => $this->roleCatalog->tenantRoles(),
        ]);
    }

    private function actorId(Request $request): mixed
    {
        return $request->user()?->getAuthIdentifier();
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message' =>
                'User not found in the selected tenant.',
        ], 404);
    }
}
