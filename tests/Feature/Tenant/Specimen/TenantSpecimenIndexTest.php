<?php

namespace Tests\Feature\Tenant\Specimen;

use Tests\Support\Tenant\SpecimenStatusTestCase;

class TenantSpecimenIndexTest extends SpecimenStatusTestCase
{
    public function test_index_is_searchable_filterable_paginated_and_tenant_scoped(): void
    {
        $firstContext = (
            $this->authenticatedSpecimenContext(
                'specimen-index-first'
            )
        );

        $secondContext = (
            $this->authenticatedSpecimenContext(
                'specimen-index-second'
            )
        );

        $patient = $this->createPatient(
            $firstContext['tenant'],
            [
                'first_name' => 'Ada',
                'last_name' => 'Needle',
            ]
        );

        $order = $this->createPendingOrderWithItems(
            $firstContext['tenant'],
            $patient
        );

        $collected = $this->createCollectedSpecimen(
            $firstContext['tenant'],
            $order,
            null,
            [
                'accession_number' => (
                    'SP-INDEX-COLLECTED'
                ),
                'specimen_type' => 'Blood',
                'collected_at' => now()->subDays(3),
            ]
        );

        $received = $this->createCollectedSpecimen(
            $firstContext['tenant'],
            $order,
            null,
            [
                'accession_number' => (
                    'SP-INDEX-RECEIVED'
                ),
                'specimen_type' => 'Blood',
                'status' => 'received',
                'collected_at' => now()->subDays(2),
                'received_at' => now(),
            ]
        );

        $this->createCollectedSpecimen(
            $firstContext['tenant'],
            $order,
            null,
            [
                'accession_number' => (
                    'SP-INDEX-REJECTED'
                ),
                'specimen_type' => 'Urine',
                'status' => 'rejected',
                'collected_at' => now()->subDay(),
                'rejected_at' => now(),
                'rejection_reason' => (
                    'Incorrect container'
                ),
            ]
        );

        $secondPatient = $this->createPatient(
            $secondContext['tenant']
        );

        $secondOrder = (
            $this->createPendingOrderWithItems(
                $secondContext['tenant'],
                $secondPatient
            )
        );

        $this->createCollectedSpecimen(
            $secondContext['tenant'],
            $secondOrder,
            null,
            [
                'accession_number' => (
                    'SP-FOREIGN-ONLY'
                ),
            ]
        );

        $response = $this
            ->withHeaders(
                $firstContext['headers']
            )
            ->getJson(
                '/api/specimens?per_page=2'
            );

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonMissing([
                'accession_number' => (
                    'SP-FOREIGN-ONLY'
                ),
            ]);

        $searchResponse = $this
            ->withHeaders(
                $firstContext['headers']
            )
            ->getJson(
                '/api/specimens?search='
                .urlencode('Ada Needle')
            );

        $searchResponse
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonMissing([
                'accession_number' => (
                    'SP-FOREIGN-ONLY'
                ),
            ]);

        $query = http_build_query([
            'status' => 'received',
            'order_uuid' => $order->uuid,
            'specimen_type' => 'Blood',
            'date_from' => (
                now()->subDays(3)->toDateString()
            ),
            'date_to' => now()->toDateString(),
        ]);

        $filteredResponse = $this
            ->withHeaders(
                $firstContext['headers']
            )
            ->getJson(
                '/api/specimens?'.$query
            );

        $filteredResponse
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.uuid',
                $received->uuid
            )
            ->assertJsonPath(
                'data.0.status',
                'received'
            )
            ->assertJsonPath(
                'data.0.order.uuid',
                $order->uuid
            )
            ->assertJsonPath(
                'data.0.order.patient.first_name',
                'Ada'
            );

        $this->assertNotSame(
            $collected->uuid,
            $filteredResponse->json(
                'data.0.uuid'
            )
        );
    }

    public function test_index_query_parameters_are_validated(): void
    {
        $context = $this->authenticatedSpecimenContext(
            'specimen-index-validation'
        );

        $this
            ->withHeaders($context['headers'])
            ->getJson(
                '/api/specimens?'
                .http_build_query([
                    'status' => 'destroyed',
                    'order_uuid' => 'invalid',
                    'date_from' => '2026-07-20',
                    'date_to' => '2026-07-10',
                    'per_page' => 101,
                ])
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'status',
                'order_uuid',
                'date_to',
                'per_page',
            ]);
    }
}