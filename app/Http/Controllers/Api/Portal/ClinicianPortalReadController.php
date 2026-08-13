<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\IndexClinicianPortalPatientsRequest;
use App\Http\Requests\Portal\IndexPortalOrdersRequest;
use App\Http\Requests\Portal\IndexPortalReportsRequest;
use App\Http\Resources\Portal\ClinicianPortalOrderResource;
use App\Http\Resources\Portal\ClinicianPortalPatientResource;
use App\Http\Resources\Portal\PortalLaboratoryReportResource;
use App\Models\ClinicianPortalAccount;
use App\Services\Tenant\LaboratoryReport\LaboratoryReportPdfService;
use App\Services\Tenant\Portal\ClinicianPortalReadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ClinicianPortalReadController extends Controller
{
    public function __construct(
        private readonly ClinicianPortalReadService $readService,
        private readonly LaboratoryReportPdfService $pdfService,
    ) {
    }

    public function patients(
        IndexClinicianPortalPatientsRequest $request
    ): AnonymousResourceCollection {
        return ClinicianPortalPatientResource::collection(
            $this->readService->paginatePatients(
                $this->account($request),
                $request->validated()
            )
        );
    }

    public function orders(
        IndexPortalOrdersRequest $request
    ): AnonymousResourceCollection {
        return ClinicianPortalOrderResource::collection(
            $this->readService->paginateOrders(
                $this->account($request),
                $request->validated()
            )
        );
    }

    public function order(
        Request $request,
        string $order
    ): ClinicianPortalOrderResource {
        return new ClinicianPortalOrderResource(
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
    ): ClinicianPortalAccount {
        $account = $request->attributes->get(
            'portal_account'
        );

        if (! $account instanceof ClinicianPortalAccount) {
            abort(401, 'Unauthenticated.');
        }

        return $account;
    }
}
