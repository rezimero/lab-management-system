<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestResult\IndexTestResultRequest;
use App\Http\Requests\TestResult\StoreTestResultRequest;
use App\Http\Requests\TestResult\UpdateTestResultRequest;
use App\Http\Resources\TestResultResource;
use App\Models\LaboratoryOrderItem;
use App\Models\Tenant\User as TenantUser;
use App\Models\TestResult;
use App\Services\Tenant\TestResult\TestResultManagementService;
use App\Services\Tenant\TestResult\TestResultQueryService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TestResultController extends Controller
{
    public function __construct(
        private readonly TestResultQueryService $queryService,
        private readonly TestResultManagementService $managementService
    ) {
    }

    /**
     * List order-driven test results.
     */
    public function index(
        IndexTestResultRequest $request
    ): AnonymousResourceCollection {
        return TestResultResource::collection(
            $this->queryService->paginate(
                $request->validated()
            )
        );
    }

    /**
     * Return result workflow statistics.
     */
    public function stats(
        IndexTestResultRequest $request
    ): JsonResponse {
        return response()->json(
            $this->queryService->statistics()
        );
    }

    /**
     * Display one order-driven test result.
     */
    public function show(
        Request $request,
        TestResult $testResult
    ): TestResultResource {
        $user = $request->user();

        abort_unless(
            $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::VIEW_TEST_RESULTS
            ),
            403
        );

        return new TestResultResource(
            $this->queryService->find(
                $testResult->uuid
            )
        );
    }

    /**
     * Create the draft result for an order item.
     */
    public function store(
        StoreTestResultRequest $request,
        LaboratoryOrderItem $laboratoryOrderItem
    ): JsonResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof TenantUser,
            403
        );

        $result = $this->managementService
            ->createDraft(
                $laboratoryOrderItem->uuid,
                $request->validated(),
                (int) $user->id
            );

        return (new TestResultResource(
            $result
        ))
            ->additional([
                'message' => (
                    'Draft test result created successfully.'
                ),
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update a draft or rejected result.
     */
    public function update(
        UpdateTestResultRequest $request,
        TestResult $testResult
    ): JsonResponse {
        $result = $this->managementService
            ->updateDraft(
                $testResult->uuid,
                $request->validated()
            );

        return (new TestResultResource(
            $result
        ))
            ->additional([
                'message' => (
                    'Draft test result updated successfully.'
                ),
            ])
            ->response();
    }
}
