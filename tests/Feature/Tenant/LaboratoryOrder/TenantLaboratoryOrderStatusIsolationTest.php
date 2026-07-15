<?php

namespace Tests\Feature\Tenant\LaboratoryOrder;

use Tests\Support\Tenant\LaboratoryOrderManagementTestCase;

class TenantLaboratoryOrderStatusIsolationTest extends LaboratoryOrderManagementTestCase
{
    public function test_tenant_cannot_update_another_tenants_order_status(): void
    {
        $context = $this
            ->authenticatedLaboratoryOrderContext(
                'order-status-owner'
            );

        $foreignContext = $this
            ->authenticatedLaboratoryOrderContext(
                'order-status-foreign'
            );

        $foreignPatient = $this->createPatient(
            $foreignContext['tenant']
        );

        $foreignOrder = $this->createLaboratoryOrder(
            $foreignContext['tenant'],
            $foreignPatient
        );

        $this->withHeaders(
            $context['headers']
        )
            ->patchJson(
                '/api/laboratory-orders/'
                .$foreignOrder->uuid
                .'/status',
                [
                    'status' => 'sample_collected',
                ]
            )
            ->assertNotFound();

        $storedOrder = $this->findLaboratoryOrder(
            $foreignContext['tenant'],
            $foreignOrder->uuid
        );

        self::assertNotNull($storedOrder);
        self::assertSame(
            'pending',
            $storedOrder->status
        );
        self::assertNull(
            $storedOrder->sample_collected_at
        );
    }
}
