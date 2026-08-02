<?php

namespace Tests\Feature\Tenant\Commercial;

use Tests\Support\Tenant\LaboratoryOrderManagementTestCase;

class TenantBillingCommercialFlowTest extends LaboratoryOrderManagementTestCase
{
    public function test_order_invoice_payment_and_receipt_flow_is_complete(): void
    {
        $context = $this->authenticatedLaboratoryOrderContext(
            'commercial-billing'
        );

        $patient = $this->createPatient($context['tenant']);
        $labTest = $this->createLabTest(
            $context['tenant'],
            [
                'name' => 'Commercial Chemistry Panel',
                'price' => '15000.00',
            ]
        );

        $orderResponse = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/laboratory-orders', [
                'patient_id' => $patient->getKey(),
                'priority' => 'routine',
                'items' => [
                    ['lab_test_id' => $labTest->getKey()],
                ],
            ]);

        $orderResponse
            ->assertCreated()
            ->assertJsonPath('data.total_amount', '15000.00');

        $orderUuid = $orderResponse->json('data.uuid');

        $invoiceResponse = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/invoices', [
                'laboratory_order_uuid' => $orderUuid,
                'discount_amount' => '1000.00',
                'tax_amount' => '0.00',
                'notes' => 'Commercial beta invoice',
            ]);

        $invoiceResponse
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.subtotal', '15000.00')
            ->assertJsonPath('data.total_amount', '14000.00')
            ->assertJsonPath('data.balance_due', '14000.00');

        $invoiceUuid = $invoiceResponse->json('data.uuid');

        $this->withHeaders($context['headers'])
            ->postJson("/api/invoices/{$invoiceUuid}/issue")
            ->assertOk()
            ->assertJsonPath('data.status', 'issued');

        $paymentResponse = $this
            ->withHeaders($context['headers'])
            ->postJson("/api/invoices/{$invoiceUuid}/payments", [
                'amount' => '5000.00',
                'method' => 'bank_transfer',
                'reference' => 'BANK-REF-001',
            ]);

        $paymentResponse
            ->assertCreated()
            ->assertJsonPath('data.amount', '5000.00')
            ->assertJsonPath('data.method', 'bank_transfer')
            ->assertJsonPath('data.invoice.status', 'partially_paid')
            ->assertJsonPath('data.invoice.balance_due', '9000.00');

        $paymentUuid = $paymentResponse->json('data.uuid');
        $receiptNumber = $paymentResponse->json('data.receipt_number');

        $this->assertIsString($receiptNumber);
        $this->assertStringStartsWith('RCP-', $receiptNumber);

        $this->withHeaders($context['headers'])
            ->getJson("/api/payments/{$paymentUuid}/receipt")
            ->assertOk()
            ->assertJsonPath('data.receipt_number', $receiptNumber)
            ->assertJsonPath('data.invoice.invoice_number', $invoiceResponse->json('data.invoice_number'));

        $this->withHeaders($context['headers'])
            ->postJson("/api/invoices/{$invoiceUuid}/payments", [
                'amount' => '9000.00',
                'method' => 'cash',
            ])
            ->assertCreated()
            ->assertJsonPath('data.invoice.status', 'paid')
            ->assertJsonPath('data.invoice.balance_due', '0.00');
    }

    public function test_payment_cannot_exceed_invoice_balance(): void
    {
        $context = $this->authenticatedLaboratoryOrderContext(
            'commercial-payment-limit'
        );
        $patient = $this->createPatient($context['tenant']);
        $labTest = $this->createLabTest($context['tenant'], ['price' => '1000.00']);

        $orderUuid = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/laboratory-orders', [
                'patient_id' => $patient->getKey(),
                'priority' => 'routine',
                'items' => [['lab_test_id' => $labTest->getKey()]],
            ])
            ->assertCreated()
            ->json('data.uuid');

        $invoiceUuid = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/invoices', [
                'laboratory_order_uuid' => $orderUuid,
            ])
            ->assertCreated()
            ->json('data.uuid');

        $this->withHeaders($context['headers'])
            ->postJson("/api/invoices/{$invoiceUuid}/issue")
            ->assertOk();

        $this->withHeaders($context['headers'])
            ->postJson("/api/invoices/{$invoiceUuid}/payments", [
                'amount' => '1000.01',
                'method' => 'cash',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
    }

    public function test_invoice_discount_cannot_exceed_subtotal_and_tax(): void
    {
        $context = $this->authenticatedLaboratoryOrderContext(
            'commercial-discount-limit'
        );
        $patient = $this->createPatient($context['tenant']);
        $labTest = $this->createLabTest(
            $context['tenant'],
            ['price' => '1000.00']
        );

        $orderUuid = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/laboratory-orders', [
                'patient_id' => $patient->getKey(),
                'priority' => 'routine',
                'items' => [['lab_test_id' => $labTest->getKey()]],
            ])
            ->assertCreated()
            ->json('data.uuid');

        $this->withHeaders($context['headers'])
            ->postJson('/api/invoices', [
                'laboratory_order_uuid' => $orderUuid,
                'discount_amount' => '1000.01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('discount_amount');
    }

    public function test_invoice_amounts_reject_more_than_two_decimal_places(): void
    {
        $context = $this->authenticatedLaboratoryOrderContext(
            'commercial-money-precision'
        );
        $patient = $this->createPatient($context['tenant']);
        $labTest = $this->createLabTest(
            $context['tenant'],
            ['price' => '1000.00']
        );

        $orderUuid = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/laboratory-orders', [
                'patient_id' => $patient->getKey(),
                'priority' => 'routine',
                'items' => [['lab_test_id' => $labTest->getKey()]],
            ])
            ->assertCreated()
            ->json('data.uuid');

        $this->withHeaders($context['headers'])
            ->postJson('/api/invoices', [
                'laboratory_order_uuid' => $orderUuid,
                'tax_amount' => '0.001',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tax_amount');
    }

    public function test_corporate_invoice_cannot_exceed_credit_limit(): void
    {
        $context = $this->authenticatedLaboratoryOrderContext(
            'commercial-credit-limit'
        );
        $patient = $this->createPatient($context['tenant']);
        $labTest = $this->createLabTest(
            $context['tenant'],
            ['price' => '1500.00']
        );

        $accountUuid = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/corporate-accounts', [
                'code' => 'CREDIT-LIMIT',
                'name' => 'Limited Corporate Account',
                'credit_limit' => '1000.00',
                'is_active' => true,
            ])
            ->assertCreated()
            ->json('data.uuid');

        $orderUuid = $this
            ->withHeaders($context['headers'])
            ->postJson('/api/laboratory-orders', [
                'patient_id' => $patient->getKey(),
                'priority' => 'routine',
                'items' => [['lab_test_id' => $labTest->getKey()]],
            ])
            ->assertCreated()
            ->json('data.uuid');

        $this->withHeaders($context['headers'])
            ->postJson('/api/invoices', [
                'laboratory_order_uuid' => $orderUuid,
                'corporate_account_uuid' => $accountUuid,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('corporate_account_uuid');
    }
}
