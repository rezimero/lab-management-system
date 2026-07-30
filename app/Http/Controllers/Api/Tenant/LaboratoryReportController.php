<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\LaboratoryReport\GenerateLaboratoryReportRequest;
use App\Http\Requests\LaboratoryReport\IndexLaboratoryReportRequest;
use App\Http\Requests\LaboratoryReport\ViewLaboratoryReportRequest;
use App\Http\Resources\LaboratoryReportResource;
use App\Models\LaboratoryOrder;
use App\Models\LaboratoryReport;
use App\Services\Tenant\LaboratoryReport\LaboratoryReportGenerationService;
use App\Services\Tenant\LaboratoryReport\LaboratoryReportPdfService;
use App\Services\Tenant\LaboratoryReport\LaboratoryReportQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class LaboratoryReportController extends Controller
{
    public function __construct(
        private readonly LaboratoryReportGenerationService
            $generationService,
        private readonly LaboratoryReportQueryService
            $queryService,
        private readonly LaboratoryReportPdfService
            $pdfService
    ) {
    }

    /**
     * List issued tenant reports.
     */
    public function index(
        IndexLaboratoryReportRequest $request
    ): AnonymousResourceCollection {
        return LaboratoryReportResource::collection(
            $this->queryService->paginate(
                $request->validated()
            )
        );
    }

    /**
     * Generate the immutable report for an order.
     */
    public function storeForOrder(
        GenerateLaboratoryReportRequest $request,
        LaboratoryOrder $laboratoryOrder
    ): JsonResponse {
        $report = $this->generationService
            ->generate(
                $laboratoryOrder->uuid,
                (int) $request->user()->id
            );

        return (
            new LaboratoryReportResource(
                $report
            )
        )
            ->additional([
                'message' => (
                    'Laboratory report generated '
                    .'successfully.'
                ),
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Retrieve the report issued for an order.
     */
    public function showForOrder(
        ViewLaboratoryReportRequest $request,
        LaboratoryOrder $laboratoryOrder
    ): LaboratoryReportResource {
        return new LaboratoryReportResource(
            $this->queryService
                ->findForOrder(
                    $laboratoryOrder
                )
        );
    }

    /**
     * Retrieve one report by public UUID.
     */
    public function show(
        ViewLaboratoryReportRequest $request,
        LaboratoryReport $laboratoryReport
    ): LaboratoryReportResource {
        return new LaboratoryReportResource(
            $laboratoryReport->load(
                'generatedBy'
            )
        );
    }

    /**
     * Download one immutable report as a PDF.
     */
    public function downloadPdf(
        ViewLaboratoryReportRequest $request,
        LaboratoryReport $laboratoryReport
    ): Response {
        return $this->pdfService->download(
            $laboratoryReport
        );
    }
}
