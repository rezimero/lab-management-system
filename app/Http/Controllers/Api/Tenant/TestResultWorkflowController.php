<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\TestResult\RejectTestResultRequest;
use App\Http\Resources\TestResultResource;
use App\Models\Tenant\User as TenantUser;
use App\Models\TestResult;
use App\Services\Tenant\TestResult\TestResultWorkflowService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TestResultWorkflowController extends Controller
{
    public function __construct(
        private readonly TestResultWorkflowService $service
    ) {
    }

    /**
     * Submit a draft result for review.
     */
    public function submit(
        Request $request,
        TestResult $testResult
    ): JsonResponse {
        $user = $this->authorizedUser(
            $request,
            TenantUserPermissionCatalog::EDIT_TEST_RESULTS
        );

        $result = $this->service->submit(
            $testResult->uuid,
            (int) $user->id
        );

        return $this->response(
            $result,
            'Test result submitted for review successfully.'
        );
    }

    /**
     * Approve a submitted result.
     */
    public function approve(
        Request $request,
        TestResult $testResult
    ): JsonResponse {
        $user = $this->authorizedUser(
            $request,
            TenantUserPermissionCatalog::APPROVE_TEST_RESULTS
        );

        $result = $this->service->approve(
            $testResult->uuid,
            (int) $user->id
        );

        return $this->response(
            $result,
            'Test result approved successfully.'
        );
    }

    /**
     * Reject a submitted result.
     */
    public function reject(
        RejectTestResultRequest $request,
        TestResult $testResult
    ): JsonResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof TenantUser,
            403
        );

        $result = $this->service->reject(
            $testResult->uuid,
            $request->validated(
                'rejection_reason'
            ),
            (int) $user->id
        );

        return $this->response(
            $result,
            'Test result rejected for correction.'
        );
    }

    /**
     * Return an authorized tenant user.
     */
    private function authorizedUser(
        Request $request,
        string $permission
    ): TenantUser {
        $user = $request->user();

        abort_unless(
            $user instanceof TenantUser
            && $user->hasPermission($permission),
            403
        );

        return $user;
    }

    /**
     * Build a workflow response.
     */
    private function response(
        TestResult $result,
        string $message
    ): JsonResponse {
        return (new TestResultResource(
            $result
        ))
            ->additional([
                'message' => $message,
            ])
            ->response();
    }
}
