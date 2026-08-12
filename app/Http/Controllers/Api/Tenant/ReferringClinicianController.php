<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\IndexReferringClinicianRequest;
use App\Http\Requests\Portal\StoreReferringClinicianRequest;
use App\Http\Requests\Portal\UpdateReferringClinicianRequest;
use App\Http\Resources\ReferringClinicianResource;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\Portal\ReferringClinicianQueryService;
use App\Services\Tenant\Portal\ReferringClinicianService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class ReferringClinicianController extends Controller
{
    public function __construct(
        protected ReferringClinicianQueryService $queryService,
        protected ReferringClinicianService $service,
    ) {
    }

    public function index(
        IndexReferringClinicianRequest $request
    ): AnonymousResourceCollection {
        $user = $this->user($request);

        $this->requirePermission(
            $user,
            TenantUserPermissionCatalog::VIEW_REFERRING_CLINICIANS
        );

        $validated = $request->validated();

        return ReferringClinicianResource::collection(
            $this->queryService->paginate(
                search: $validated['search'] ?? null,
                isActive: array_key_exists(
                    'is_active',
                    $validated
                )
                    ? (bool) $validated['is_active']
                    : null,
                perPage: (int) (
                    $validated['per_page'] ?? 25
                ),
            )
        );
    }

    public function store(
        StoreReferringClinicianRequest $request
    ): JsonResponse {
        $user = $this->user($request);

        $this->requirePermission(
            $user,
            TenantUserPermissionCatalog::MANAGE_PORTAL_ACCESS
        );

        $clinician = $this->service->create(
            $request->validated(),
            $user
        );

        return (new ReferringClinicianResource($clinician))
            ->additional([
                'message' => (
                    'Referring clinician created successfully.'
                ),
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateReferringClinicianRequest $request,
        string $referringClinician
    ): JsonResponse {
        $user = $this->user($request);

        $this->requirePermission(
            $user,
            TenantUserPermissionCatalog::MANAGE_PORTAL_ACCESS
        );

        $clinician = $this->queryService->find(
            $referringClinician
        );

        return (new ReferringClinicianResource(
            $this->service->update(
                $clinician,
                $request->validated(),
                $user
            )
        ))
            ->additional([
                'message' => (
                    'Referring clinician updated successfully.'
                ),
            ])
            ->response();
    }

    public function activate(
        Request $request,
        string $referringClinician
    ): JsonResponse {
        return $this->setActive(
            $request,
            $referringClinician,
            true
        );
    }

    public function deactivate(
        Request $request,
        string $referringClinician
    ): JsonResponse {
        return $this->setActive(
            $request,
            $referringClinician,
            false
        );
    }

    private function setActive(
        Request $request,
        string $uuid,
        bool $isActive
    ): JsonResponse {
        $user = $this->user($request);

        $this->requirePermission(
            $user,
            TenantUserPermissionCatalog::MANAGE_PORTAL_ACCESS
        );

        $clinician = $this->queryService->find($uuid);

        return (new ReferringClinicianResource(
            $this->service->setActive(
                $clinician,
                $isActive,
                $user
            )
        ))
            ->additional([
                'message' => $isActive
                    ? 'Referring clinician activated successfully.'
                    : 'Referring clinician deactivated successfully.',
            ])
            ->response();
    }

    private function user(
        Request $request
    ): TenantUser {
        $user = $request->user();

        if (! $user instanceof TenantUser) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }

    private function requirePermission(
        TenantUser $user,
        string $permission
    ): void {
        abort_unless(
            $user->hasPermission($permission),
            403,
            'You do not have permission to perform this action.'
        );
    }
}
