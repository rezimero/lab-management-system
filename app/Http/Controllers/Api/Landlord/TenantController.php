<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Tenant\ResetTenantAdminPasswordRequest;
use App\Http\Requests\Landlord\Tenant\StoreTenantRequest;
use App\Http\Requests\Landlord\Tenant\UpdateTenantRequest;
use App\Models\Tenant;
use App\Services\Landlord\Tenant\LandlordTenantManagementService;
use App\Services\Landlord\Tenant\LandlordTenantProvisioningService;
use App\Services\Landlord\Tenant\LandlordTenantStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class TenantController extends Controller
{
    public function __construct(
        protected LandlordTenantStatisticsService $statisticsService,
        protected LandlordTenantProvisioningService $provisioningService,
        protected LandlordTenantManagementService $managementService,
    ) {
    }

    public function index(): JsonResponse
    {
        try {
            $tenants = Tenant::query()->get();

            return response()->json([
                'tenants' => $tenants
                    ->map(
                        fn (Tenant $tenant): array =>
                            $this->statisticsService
                                ->tenantWithStats($tenant)
                    )
                    ->values()
                    ->all(),
                'total' => $tenants->count(),
            ]);
        } catch (Throwable $exception) {
            return $this->failure(
                'Failed to fetch tenants.',
                'Failed to fetch tenants',
                $exception
            );
        }
    }

    public function show(int|string $id): JsonResponse
    {
        try {
            $tenant = $this->findTenant($id);

            if ($tenant === null) {
                return $this->notFound();
            }

            return response()->json(
                $this->statisticsService
                    ->tenantWithStats($tenant)
            );
        } catch (Throwable $exception) {
            return $this->failure(
                'Failed to fetch tenant.',
                'Failed to fetch tenant',
                $exception
            );
        }
    }

    public function store(
        StoreTenantRequest $request
    ): JsonResponse {
        try {
            $tenant = $this->provisioningService
                ->provision($request->validated());

            return response()->json([
                'message' => 'Tenant created successfully',
                'tenant' => $this->statisticsService
                    ->tenantWithStats($tenant),
            ], 201);
        } catch (Throwable $exception) {
            return $this->failure(
                'Failed to create tenant.',
                'Failed to create tenant: '
                    .$exception->getMessage(),
                $exception
            );
        }
    }

    public function update(
        UpdateTenantRequest $request,
        int|string $id
    ): JsonResponse {
        try {
            $tenant = $this->findTenant($id);

            if ($tenant === null) {
                return $this->notFound();
            }

            $tenant = $this->managementService->update(
                $tenant,
                $request->validated()
            );

            return response()->json([
                'message' => 'Tenant updated successfully',
                'tenant' => $this->statisticsService
                    ->tenantWithStats($tenant),
            ]);
        } catch (Throwable $exception) {
            return $this->failure(
                'Failed to update tenant.',
                'Failed to update tenant',
                $exception
            );
        }
    }

    public function destroy(int|string $id): JsonResponse
    {
        try {
            $tenant = $this->findTenant($id);

            if ($tenant === null) {
                return $this->notFound();
            }

            $this->managementService->delete($tenant);

            return response()->json([
                'message' => 'Tenant deleted successfully',
            ]);
        } catch (Throwable $exception) {
            return $this->failure(
                'Failed to delete tenant.',
                'Failed to delete tenant',
                $exception
            );
        }
    }

    public function suspend(int|string $id): JsonResponse
    {
        return $this->changeStatus(
            id: $id,
            active: false,
            message: 'Tenant suspended successfully',
            failureMessage: 'Failed to suspend tenant',
        );
    }

    public function activate(int|string $id): JsonResponse
    {
        return $this->changeStatus(
            id: $id,
            active: true,
            message: 'Tenant activated successfully',
            failureMessage: 'Failed to activate tenant',
        );
    }

    public function resetAdminPassword(
        ResetTenantAdminPasswordRequest $request,
        int|string $id
    ): JsonResponse {
        try {
            $tenant = $this->findTenant($id);

            if ($tenant === null) {
                return $this->notFound();
            }

            $validated = $request->validated();

            $user = $this->managementService
                ->resetAdminPassword(
                    $tenant,
                    $validated['email'],
                    $validated['new_password']
                );

            if ($user === null) {
                return response()->json([
                    'message' => 'User not found in tenant',
                ], 404);
            }

            return response()->json([
                'message' => 'Password reset successfully',
                'user' => $user,
            ]);
        } catch (Throwable $exception) {
            return $this->failure(
                'Failed to reset tenant admin password.',
                'Failed to reset password',
                $exception
            );
        }
    }

    public function tenantStats(
        int|string $id
    ): JsonResponse {
        try {
            $tenant = $this->findTenant($id);

            if ($tenant === null) {
                return $this->notFound();
            }

            return response()->json([
                'tenant' => $tenant->name,
                'stats' => $this->statisticsService
                    ->collect($tenant),
            ]);
        } catch (Throwable $exception) {
            return $this->failure(
                'Failed to collect tenant statistics.',
                'Failed to get tenant stats',
                $exception
            );
        }
    }

    private function changeStatus(
        int|string $id,
        bool $active,
        string $message,
        string $failureMessage
    ): JsonResponse {
        try {
            $tenant = $this->findTenant($id);

            if ($tenant === null) {
                return $this->notFound();
            }

            $tenant = $this->managementService
                ->setActive($tenant, $active);

            return response()->json([
                'message' => $message,
                'tenant' => $this->statisticsService
                    ->tenantWithStats($tenant),
            ]);
        } catch (Throwable $exception) {
            return $this->failure(
                $failureMessage.'.',
                $failureMessage,
                $exception
            );
        }
    }

    private function findTenant(
        int|string $id
    ): ?Tenant {
        return Tenant::query()->find($id);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message' => 'Tenant not found',
        ], 404);
    }

    private function failure(
        string $logMessage,
        string $responseMessage,
        Throwable $exception
    ): JsonResponse {
        Log::error($logMessage, [
            'error' => $exception->getMessage(),
        ]);

        return response()->json([
            'message' => $responseMessage,
        ], 500);
    }
}