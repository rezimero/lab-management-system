<?php

namespace Tests\Feature\Tenant\Specimen;

use Tests\Support\Tenant\SpecimenStatusTestCase;

class TenantSpecimenStatsTest extends SpecimenStatusTestCase
{
    public function test_stats_are_tenant_scoped_and_grouped_by_status(): void
    {
        $firstContext = (
            $this->authenticatedSpecimenContext(
                'specimen-stats-first'
            )
        );

        $secondContext = (
            $this->authenticatedSpecimenContext(
                'specimen-stats-second'
            )
        );

        $firstPatient = $this->createPatient(
            $firstContext['tenant']
        );

        $firstOrder = (
            $this->createPendingOrderWithItems(
                $firstContext['tenant'],
                $firstPatient
            )
        );

        $this->createCollectedSpecimen(
            $firstContext['tenant'],
            $firstOrder
        );

        $this->createCollectedSpecimen(
            $firstContext['tenant'],
            $firstOrder,
            null,
            [
                'status' => 'received',
                'received_at' => now(),
            ]
        );

        $this->createCollectedSpecimen(
            $firstContext['tenant'],
            $firstOrder,
            null,
            [
                'status' => 'rejected',
                'rejected_at' => now(),
                'rejection_reason' => (
                    'Insufficient volume'
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
            $secondOrder
        );

        $this
            ->withHeaders(
                $firstContext['headers']
            )
            ->getJson('/api/specimens/stats')
            ->assertOk()
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath(
                'data.collected',
                1
            )
            ->assertJsonPath(
                'data.received',
                1
            )
            ->assertJsonPath(
                'data.rejected',
                1
            );
    }
}