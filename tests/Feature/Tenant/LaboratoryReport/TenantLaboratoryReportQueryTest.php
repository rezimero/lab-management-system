<?php

namespace Tests\Feature\Tenant\LaboratoryReport;

use Tests\Support\Tenant\LaboratoryReportTestCase;

class TenantLaboratoryReportQueryTest extends
    LaboratoryReportTestCase
{
    public function test_reports_can_be_listed_and_retrieved(): void
    {
        $context = $this->authenticatedReportContext(
            'report-query'
        );

        $firstOrder = $this->createReportReadyOrder(
            $context['tenant'],
            $context['headers']
        );

        $first = $this
            ->generateReport(
                $context['headers'],
                $firstOrder
            )
            ->assertCreated();

        $secondOrder = $this->createReportReadyOrder(
            $context['tenant'],
            $context['headers']
        );

        $second = $this
            ->generateReport(
                $context['headers'],
                $secondOrder
            )
            ->assertCreated();

        $firstUuid = $first->json(
            'data.uuid'
        );

        $secondUuid = $second->json(
            'data.uuid'
        );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-reports'
            )
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                2
            );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-reports/'
                .$firstUuid
            )
            ->assertOk()
            ->assertJsonPath(
                'data.uuid',
                $firstUuid
            );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-orders/'
                .$secondOrder->uuid
                .'/report'
            )
            ->assertOk()
            ->assertJsonPath(
                'data.uuid',
                $secondUuid
            );
    }

    public function test_reports_support_order_and_search_filters(): void
    {
        $context = $this->authenticatedReportContext(
            'report-filter'
        );

        $firstOrder = $this->createReportReadyOrder(
            $context['tenant'],
            $context['headers']
        );

        $first = $this
            ->generateReport(
                $context['headers'],
                $firstOrder
            )
            ->assertCreated();

        $secondOrder = $this->createReportReadyOrder(
            $context['tenant'],
            $context['headers']
        );

        $this
            ->generateReport(
                $context['headers'],
                $secondOrder
            )
            ->assertCreated();

        $reportNumber = $first->json(
            'data.report_number'
        );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-reports'
                .'?order_uuid='
                .$firstOrder->uuid
            )
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                1
            )
            ->assertJsonPath(
                'data.0.order.uuid',
                $firstOrder->uuid
            );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/laboratory-reports'
                .'?search='
                .urlencode($reportNumber)
            )
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                1
            )
            ->assertJsonPath(
                'data.0.report_number',
                $reportNumber
            );
    }
}
