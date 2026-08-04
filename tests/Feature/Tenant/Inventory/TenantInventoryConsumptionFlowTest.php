<?php

namespace Tests\Feature\Tenant\Inventory;

use Tests\Support\Tenant\InventoryManagementTestCase;

class TenantInventoryConsumptionFlowTest extends InventoryManagementTestCase
{
    public function test_test_requirements_are_suggestions_and_consumption_is_explicit(): void
    {
        $context = $this->authenticatedInventoryContext('inventory-consumption');
        $branch = $this->headOffice($context['tenant']);
        $catalogue = $this->createInventoryCatalogue(
            $context['headers'],
            $branch->uuid,
            'CONSUME'
        );
        $received = $this->receiveInventory(
            $context['headers'],
            $branch->uuid,
            $catalogue['item_uuid'],
            '12.000000'
        );
        $patient = $this->createPatient($context['tenant']);
        $labTest = $this->createLabTest($context['tenant'], [
            'name' => 'Inventory-linked Chemistry Test',
        ]);

        $this->withHeaders($context['headers'])
            ->putJson('/api/lab-tests/'.$labTest->uuid.'/inventory-requirements', [
                'requirements' => [[
                    'item_uuid' => $catalogue['item_uuid'],
                    'quantity' => '2.000000',
                    'is_active' => true,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('data.requirements.0.quantity', '2.000000');

        $order = $this->withHeaders($context['headers'])
            ->postJson('/api/laboratory-orders', [
                'patient_id' => $patient->getKey(),
                'processing_branch_uuid' => $branch->uuid,
                'collection_branch_uuid' => $branch->uuid,
                'priority' => 'routine',
                'items' => [['lab_test_id' => $labTest->getKey()]],
            ])
            ->assertCreated();

        $orderItemUuid = $order->json('data.items.0.uuid');

        $this->assertSame(1, $this->tenantInventoryCount(
            $context['tenant'],
            'inventory_stock_movements'
        ));

        $this->withHeaders($context['headers'])
            ->getJson('/api/laboratory-order-items/'.$orderItemUuid.'/inventory-consumptions')
            ->assertOk()
            ->assertJsonPath('data.suggestions.0.quantity', '2.000000')
            ->assertJsonPath('data.available_lots.0.quantity_on_hand', '12.000000')
            ->assertJsonCount(0, 'data.consumptions');

        $this->withHeaders($context['headers'])
            ->postJson('/api/laboratory-order-items/'.$orderItemUuid.'/inventory-consumptions', [
                'consumed_at' => now()->toISOString(),
                'notes' => 'Actual reagent used for the run',
                'lines' => [[
                    'lot_uuid' => $received['lot_uuid'],
                    'quantity' => '2.500000',
                ]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.movements.0.movement_type', 'consumption')
            ->assertJsonPath('data.movements.0.quantity_after', '9.500000');

        $this->assertSame(2, $this->tenantInventoryCount(
            $context['tenant'],
            'inventory_stock_movements'
        ));
    }

    public function test_expired_lot_cannot_be_consumed(): void
    {
        $context = $this->authenticatedInventoryContext('inventory-expired');
        $branch = $this->headOffice($context['tenant']);
        $catalogue = $this->createInventoryCatalogue(
            $context['headers'],
            $branch->uuid,
            'EXPIRED'
        );

        $receipt = $this->withHeaders($context['headers'])
            ->postJson('/api/inventory/receipts', [
                'branch_uuid' => $branch->uuid,
                'received_at' => now()->subMonths(2)->toISOString(),
                'lines' => [[
                    'item_uuid' => $catalogue['item_uuid'],
                    'expires_at' => now()->subMonth()->toDateString(),
                    'quantity' => '5.000000',
                ]],
            ])
            ->assertCreated();

        $patient = $this->createPatient($context['tenant']);
        $labTest = $this->createLabTest($context['tenant']);
        $orderItemUuid = $this->withHeaders($context['headers'])
            ->postJson('/api/laboratory-orders', [
                'patient_id' => $patient->getKey(),
                'priority' => 'routine',
                'items' => [['lab_test_id' => $labTest->getKey()]],
            ])
            ->assertCreated()
            ->json('data.items.0.uuid');

        $this->withHeaders($context['headers'])
            ->postJson('/api/laboratory-order-items/'.$orderItemUuid.'/inventory-consumptions', [
                'consumed_at' => now()->toISOString(),
                'lines' => [[
                    'lot_uuid' => $receipt->json('data.lots.0.uuid'),
                    'quantity' => '1.000000',
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('lines.0.lot_uuid');
    }

    public function test_cancelled_order_item_rejects_new_inventory_consumption(): void
    {
        $context = $this->authenticatedInventoryContext('inventory-cancelled-consumption');
        $branch = $this->headOffice($context['tenant']);
        $catalogue = $this->createInventoryCatalogue(
            $context['headers'],
            $branch->uuid,
            'CANCELLED-CONSUMPTION'
        );
        $received = $this->receiveInventory(
            $context['headers'],
            $branch->uuid,
            $catalogue['item_uuid'],
            '5.000000'
        );
        $patient = $this->createPatient($context['tenant']);
        $labTest = $this->createLabTest($context['tenant']);
        $order = $this->withHeaders($context['headers'])
            ->postJson('/api/laboratory-orders', [
                'patient_id' => $patient->getKey(),
                'processing_branch_uuid' => $branch->uuid,
                'collection_branch_uuid' => $branch->uuid,
                'priority' => 'routine',
                'items' => [['lab_test_id' => $labTest->getKey()]],
            ])
            ->assertCreated();

        $orderUuid = $order->json('data.uuid');
        $orderItemUuid = $order->json('data.items.0.uuid');

        $this->withHeaders($context['headers'])
            ->patchJson('/api/laboratory-orders/'.$orderUuid.'/status', [
                'status' => 'cancelled',
            ])
            ->assertOk();

        $this->withHeaders($context['headers'])
            ->postJson('/api/laboratory-order-items/'.$orderItemUuid.'/inventory-consumptions', [
                'consumed_at' => now()->toISOString(),
                'lines' => [[
                    'lot_uuid' => $received['lot_uuid'],
                    'quantity' => '1.000000',
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('laboratory_order_item');

        $this->assertSame(0, $this->tenantInventoryCount(
            $context['tenant'],
            'inventory_consumptions'
        ));
        $this->assertSame(1, $this->tenantInventoryCount(
            $context['tenant'],
            'inventory_stock_movements'
        ));
    }
}
