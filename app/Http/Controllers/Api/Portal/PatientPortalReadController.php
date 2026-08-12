<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\IndexPortalOrdersRequest;
use App\Http\Requests\Portal\IndexPortalReportsRequest;
use App\Http\Resources\Portal\PatientPortalOrderResource;
use App\Http\Resources\Portal\PortalLaboratoryReportResource;
use App\Models\PatientPortalAccount;
use App\Services\Tenant\LaboratoryReport\LaboratoryReportPdfService;
use App\Services\Tenant\Portal\PatientPortalReadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PatientPortalReadController extends Controller
{
    public function __construct(
        private readonly PatientPortalReadService $readService,
        private readonly LaboratoryReportPdfService $pdfService,
    ) {
    }

    public function orders(
        IndexPortalOrdersRequest $request
    ): AnonymousResourceCollection {
        return PatientPortalOrderResource::collection(
            $this->readService->paginateOrders(
                $this->account($request),
                $request->validated()
            )
        );
    }

    public function order(
        Request $request,
        string $order
    ): PatientPortalOrderResource {
        return new PatientPortalOrderResource(
            $this->readService->findOrder(
                $this->account($request),
                $order
            )
        );
    }

    public function reports(
        IndexPortalReportsRequest $request
    ): AnonymousResourceCollection {
        return PortalLaboratoryReportResource::collection(
            $this->readService->paginateReports(
                $this->account($request),
                $request->validated()
            )
        );
    }

    public function report(
        Request $request,
        string $report
    ): PortalLaboratoryReportResource {
        return new PortalLaboratoryReportResource(
            $this->readService->findCurrentReport(
                $this->account($request),
                $report
            )
        );
    }

    public function reportPdf(
        Request $request,
        string $report
    ): Response {
        return $this->pdfService->download(
            $this->readService->findCurrentReport(
                $this->account($request),
                $report
            )
        );
    }

    private function account(
        Request $request
    ): PatientPortalAccount {
        $account = $request->attributes->get(
            'portal_account'
        );

        if (! $account instanceof PatientPortalAccount) {
            abort(401, 'Unauthenticated.');
        }

        return $account;
    }
}
