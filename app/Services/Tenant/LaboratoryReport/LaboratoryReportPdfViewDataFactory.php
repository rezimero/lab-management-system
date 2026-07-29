<?php

namespace App\Services\Tenant\LaboratoryReport;

use App\Models\LaboratoryReport;

class LaboratoryReportPdfViewDataFactory
{
    public function __construct(
        private readonly LaboratoryReportPdfImageResolver
            $imageResolver
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(
        LaboratoryReport $report
    ): array {
        $laboratory = $this->snapshotArray(
            $report->laboratory_snapshot
        );

        $patient = $this->snapshotArray(
            $report->patient_snapshot
        );

        $order = $this->snapshotArray(
            $report->order_snapshot
        );

        $resultsSnapshot = $this->snapshotArray(
            $report->results_snapshot
        );

        $results = $resultsSnapshot['items']
            ?? [];

        if (! is_array($results)) {
            $results = [];
        }

        return [
            'reportNumber' => (
                (string) $report->report_number
            ),
            'generatedAt' => (
                $report->generated_at
                    ?->format('d M Y, H:i')
                ?? 'Not recorded'
            ),
            'laboratory' => $laboratory,
            'patient' => $patient,
            'order' => $order,
            'results' => $results,
            'logoDataUri' => (
                $this->imageResolver->resolve(
                    $report,
                    $laboratory,
                    'logo'
                )
            ),
            'signatureDataUri' => (
                $this->imageResolver->resolve(
                    $report,
                    $laboratory,
                    'director_signature'
                )
            ),
            'primaryColor' => $this->hexColor(
                $laboratory['primary_color']
                    ?? null,
                '#1976D2'
            ),
            'secondaryColor' => $this->hexColor(
                $laboratory['secondary_color']
                    ?? null,
                '#424242'
            ),
            'accentColor' => $this->hexColor(
                $laboratory['accent_color']
                    ?? null,
                '#2E7D32'
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotArray(
        mixed $value
    ): array {
        return is_array($value)
            ? $value
            : [];
    }

    private function hexColor(
        mixed $value,
        string $fallback
    ): string {
        if (
            ! is_string($value)
            || preg_match(
                '/^#[0-9A-Fa-f]{6}$/',
                $value
            ) !== 1
        ) {
            return $fallback;
        }

        return strtoupper($value);
    }
}