<?php

namespace App\Services\Tenant\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\EntitlementService;
use App\Support\Commercial\CommercialFeatureCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        protected InvoiceNumberService $numbers,
        protected EntitlementService $entitlements,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(
        Tenant $tenant,
        Invoice $invoice,
        array $data,
        TenantUser $actor
    ): Payment {
        $this->entitlements->ensure(
            $tenant,
            CommercialFeatureCatalog::BILLING
        );

        return DB::connection('tenant')->transaction(
            function () use ($invoice, $data, $actor): Payment {
                $lockedInvoice = Invoice::query()
                    ->whereKey($invoice->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array(
                    $lockedInvoice->status,
                    ['issued', 'partially_paid'],
                    true
                )) {
                    throw ValidationException::withMessages([
                        'invoice' => [
                            'Payments can be recorded only against an issued invoice.',
                        ],
                    ]);
                }

                $amountMinor = $this->toMinorUnits($data['amount']);
                $balanceMinor = $this->toMinorUnits(
                    $lockedInvoice->balance_due
                );

                if ($amountMinor <= 0 || $amountMinor > $balanceMinor) {
                    throw ValidationException::withMessages([
                        'amount' => [
                            'The payment must be greater than zero and cannot exceed the invoice balance.',
                        ],
                    ]);
                }

                $payment = Payment::query()->create([
                    'payment_number' => $this->numbers->payment(),
                    'receipt_number' => $this->numbers->receipt(),
                    'invoice_id' => $lockedInvoice->getKey(),
                    'amount' => $this->formatMinorUnits($amountMinor),
                    'method' => $data['method'],
                    'reference' => $data['reference'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'paid_at' => $data['paid_at'] ?? now(),
                    'received_by' => $actor->getKey(),
                ]);

                $paidMinor = $this->toMinorUnits(
                    $lockedInvoice->amount_paid
                ) + $amountMinor;
                $totalMinor = $this->toMinorUnits(
                    $lockedInvoice->total_amount
                );
                $remainingMinor = max(0, $totalMinor - $paidMinor);

                $lockedInvoice->forceFill([
                    'amount_paid' => $this->formatMinorUnits($paidMinor),
                    'balance_due' => $this->formatMinorUnits($remainingMinor),
                    'status' => $remainingMinor === 0
                        ? 'paid'
                        : 'partially_paid',
                    'paid_at' => $remainingMinor === 0 ? now() : null,
                    'updated_by' => $actor->getKey(),
                ])->save();

                return $payment->fresh([
                    'invoice.order',
                    'invoice.patient',
                    'invoice.branch',
                    'invoice.corporateAccount',
                    'receivedBy',
                ]);
            }
        );
    }

    private function toMinorUnits(mixed $amount): int
    {
        $normalized = trim((string) $amount);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalized)) {
            throw new \InvalidArgumentException(
                'A non-negative monetary amount with no more than two decimal places is required.'
            );
        }

        [$whole, $fraction] = array_pad(
            explode('.', $normalized, 2),
            2,
            ''
        );

        return ((int) $whole * 100)
            + (int) str_pad($fraction, 2, '0');
    }

    private function formatMinorUnits(int $amount): string
    {
        return sprintf('%d.%02d', intdiv($amount, 100), $amount % 100);
    }
}
