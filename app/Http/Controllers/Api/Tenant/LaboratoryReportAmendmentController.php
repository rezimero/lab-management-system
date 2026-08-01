<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\LaboratoryReportAmendment\ApproveLaboratoryReportAmendmentRequest;
use App\Http\Requests\LaboratoryReportAmendment\RejectLaboratoryReportAmendmentRequest;
use App\Http\Requests\LaboratoryReportAmendment\SubmitLaboratoryReportAmendmentRequest;
use App\Http\Requests\LaboratoryReportAmendment\ViewLaboratoryReportAmendmentRequest;
use App\Http\Resources\LaboratoryReportAmendmentResource;
use App\Models\LaboratoryReport;
use App\Models\LaboratoryReportAmendment;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\LaboratoryReport\LaboratoryReportAmendmentQueryService;
use App\Services\Tenant\LaboratoryReport\LaboratoryReportAmendmentReviewService;
use App\Services\Tenant\LaboratoryReport\LaboratoryReportAmendmentSubmissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use LogicException;

class LaboratoryReportAmendmentController extends Controller
{
    public function __construct(
        private readonly LaboratoryReportAmendmentSubmissionService $submissionService,
        private readonly LaboratoryReportAmendmentReviewService $reviewService,
        private readonly LaboratoryReportAmendmentQueryService $queryService
    ) {}

    public function store(
        SubmitLaboratoryReportAmendmentRequest $request,
        LaboratoryReport $laboratoryReport
    ): JsonResponse {
        $user = $this->tenantUser($request->user());

        $amendment = $this->submissionService->submit(
            $laboratoryReport->uuid,
            $request->validated(),
            $user
        );

        return (
            new LaboratoryReportAmendmentResource(
                $amendment
            )
        )
            ->additional([
                'message' => (
                    'Laboratory report amendment submitted successfully.'
                ),
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function indexForReport(
        ViewLaboratoryReportAmendmentRequest $request,
        LaboratoryReport $laboratoryReport
    ): AnonymousResourceCollection {
        return LaboratoryReportAmendmentResource::collection(
            $this->queryService->forLineage(
                $laboratoryReport
            )
        );
    }

    public function show(
        ViewLaboratoryReportAmendmentRequest $request,
        LaboratoryReportAmendment $laboratoryReportAmendment
    ): LaboratoryReportAmendmentResource {
        return new LaboratoryReportAmendmentResource(
            $this->queryService->prepare(
                $laboratoryReportAmendment
            )
        );
    }

    public function approve(
        ApproveLaboratoryReportAmendmentRequest $request,
        LaboratoryReportAmendment $laboratoryReportAmendment
    ): LaboratoryReportAmendmentResource {
        return new LaboratoryReportAmendmentResource(
            $this->reviewService->approve(
                $laboratoryReportAmendment->uuid,
                $this->tenantUser($request->user())
            )
        );
    }

    public function reject(
        RejectLaboratoryReportAmendmentRequest $request,
        LaboratoryReportAmendment $laboratoryReportAmendment
    ): LaboratoryReportAmendmentResource {
        return new LaboratoryReportAmendmentResource(
            $this->reviewService->reject(
                $laboratoryReportAmendment->uuid,
                $request->validated('rejection_reason'),
                $this->tenantUser($request->user())
            )
        );
    }

    private function tenantUser(mixed $user): TenantUser
    {
        if ($user instanceof TenantUser) {
            return $user;
        }

        throw new LogicException(
            'A tenant user is required.'
        );
    }
}
