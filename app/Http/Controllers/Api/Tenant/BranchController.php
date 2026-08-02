<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\AssignBranchUsersRequest;
use App\Http\Requests\Branch\IndexBranchRequest;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\Branch\BranchManagementService;
use App\Services\Tenant\Branch\BranchQueryService;
use App\Services\Tenant\EntitlementService;
use App\Support\Commercial\CommercialFeatureCatalog;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BranchController extends Controller
{
    public function __construct(
        protected BranchQueryService $queries,
        protected BranchManagementService $management,
        protected EntitlementService $entitlements,
    ) {}

    public function index(IndexBranchRequest $request): AnonymousResourceCollection
    {
        $this->ensureEntitlement();
        $validated = $request->validated();

        return BranchResource::collection(
            $this->queries->paginate(
                $this->user($request),
                $validated['search'] ?? null,
                $validated['type'] ?? null,
                array_key_exists('active', $validated)
                    ? (bool) $validated['active']
                    : null,
                (int) ($validated['per_page'] ?? 50)
            )
        );
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $this->ensureEntitlement();

        return (new BranchResource(
            $this->management->save(
                null,
                $request->validated(),
                $this->user($request)
            )
        ))->additional(['message' => 'Branch created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $branch): BranchResource
    {
        $this->ensureEntitlement();

        return new BranchResource(
            $this->queries->find($this->user($request), $branch)
        );
    }

    public function update(
        UpdateBranchRequest $request,
        Branch $branch
    ): JsonResponse {
        $this->ensureEntitlement();

        return (new BranchResource(
            $this->management->save(
                $branch,
                $request->validated(),
                $this->user($request)
            )
        ))->additional(['message' => 'Branch updated successfully.'])
            ->response();
    }

    public function assignUsers(
        AssignBranchUsersRequest $request,
        Branch $branch
    ): JsonResponse {
        $this->ensureEntitlement();
        $validated = $request->validated();

        return (new BranchResource(
            $this->management->assignUsers(
                $branch,
                array_map('intval', $validated['user_ids']),
                isset($validated['primary_user_id'])
                    ? (int) $validated['primary_user_id']
                    : null,
                $this->user($request)
            )
        ))->additional(['message' => 'Branch users assigned successfully.'])
            ->response();
    }

    public function deactivate(Request $request, Branch $branch): JsonResponse
    {
        $this->ensureEntitlement();
        $user = $this->user($request);

        if (! $user->hasPermission(
            TenantUserPermissionCatalog::MANAGE_BRANCHES
        )) {
            abort(403, 'Unauthorized.');
        }

        return (new BranchResource(
            $this->management->deactivate($branch, $user)
        ))->additional(['message' => 'Branch deactivated successfully.'])
            ->response();
    }

    private function ensureEntitlement(): void
    {
        $this->entitlements->ensure(
            $this->tenant(),
            CommercialFeatureCatalog::MULTI_BRANCH
        );
    }

    private function user(Request $request): TenantUser
    {
        $user = $request->user();

        if (! $user instanceof TenantUser) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }

    private function tenant(): Tenant
    {
        return Tenant::current()
            ?? abort(422, 'Tenant context is required.');
    }
}
