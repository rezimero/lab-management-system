<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Models\LaboratoryReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\Factory as ViewFactory;

class LaboratoryReportPdfService
{
    public function __construct(
        private readonly ViewFactory $view,
        private readonly LaboratoryReportPdfViewDataFactory
            $viewDataFactory,
        private readonly LaboratoryReportPdfFilename
            $filename
    ) {
    }

    public function download(
        LaboratoryReport $report
    ): Response {
        return Pdf::loadHTML(
            $this->renderHtml($report)
        )
            ->setOption([
                'defaultFont' => 'DejaVu Sans',
                'isJavascriptEnabled' => false,
                'isPhpEnabled' => false,
                'isRemoteEnabled' => false,
            ])
            ->setPaper('a4', 'portrait')
            ->download(
                $this->filename->make(
                    $report
                )
            );
    }

    public function renderHtml(
        LaboratoryReport $report
    ): string {
        return $this->view
            ->make(
                'pdf.laboratory-report',
                $this->viewDataFactory->build(
                    $report
                )
            )
            ->render();
    }
}