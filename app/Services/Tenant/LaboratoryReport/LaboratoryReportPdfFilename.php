<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Models\LaboratoryReport;
use Illuminate\Support\Str;

class LaboratoryReportPdfFilename
{
    public function make(
        LaboratoryReport $report
    ): string {
        $base = preg_replace(
            '/[^A-Za-z0-9._-]+/',
            '-',
            Str::ascii(
                (string) $report->report_number
            )
        );

        $base = trim(
            is_string($base) ? $base : '',
            '-_.'
        );

        return ($base !== ''
            ? $base
            : 'laboratory-report'
        ).'.pdf';
    }
}