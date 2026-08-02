<?php

namespace App\Services\Tenant\Billing;

use App\Models\CorporateAccount;
use App\Models\Invoice;
use App\Models\LaboratoryOrder;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\EntitlementService;
use App\Support\Commercial\CommercialFeatureCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        protected InvoiceNumberService $numbers,
        protected EntitlementService $entitlements,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFromOrder(
        Tenant $tenant,
        string $orderUuid,
        array $data,
        TenantUser $actor
    ): Invoice {
        $this->entitlements->ensure(
            $tenant,
            CommercialFeatureCatalog::BILLING
        );

        return DB::connection('tenant')->transaction(
            function () use ($orderUuid, $data, $actor): Invoice {
                $order = LaboratoryOrder::query()
                    ->with(['items', 'patient', 'collectionBranch', 'priceList'])
                    ->where('uuid', $orderUuid)
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->ensureOrderBranchAccess($order, $actor);

                if ($order->invoice()->exists()) {
                    throw ValidationException::withMessages([
                        'laboratory_order_uuid' => [
                            'An invoice already exists for this laboratory order.',
                        ],
                    ]);
                }

                $branch = $order->collectionBranch;

                if ($branch === null || ! $branch->is_active) {
                    throw ValidationException::withMessages([
                        'laboratory_order_uuid' => [
                            'The order does not have an active collection branch.',
                        ],
                    ]);
                }

                $corporateAccount = null;

                if (filled($data['corporate_account_uuid'] ?? null)) {
                    $corporateAccount = CorporateAccount::query()
                        ->where('uuid', $data['corporate_account_uuid'])
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->first();

                    if ($corporateAccount === null) {
                        throw ValidationException::withMessages([
                            'corporate_account_uuid' => [
                                'The selected corporate account is unavailable.',
                            ],
                        ]);
                    }
                }

                $subtotalMinor = $order->items->sum(
                    fn ($item): int => $this->toMinorUnits(
                        $item->price_snapshot
                    )
                );
                $discountMinor = $this->toMinorUnits(
                    $data['discount_amount'] ?? 0
                );
                $taxMinor = $this->toMinorUnits(
                    $data['tax_amount'] ?? 0
                );

                if ($discountMinor > $subtotalMinor + $taxMinor) {
                    throw ValidationException::withMessages([
                        'discount_amount' => [
                            'The discount cannot exceed the invoice subtotal plus tax.',
                        ],
                    ]);
                }

                $totalMinor = $subtotalMinor - $discountMinor + $taxMinor;

                if (
                    $corporateAccount !== null
                    && $corporateAccount->credit_limit !== null
                ) {
                    $outstandingMinor = $corporateAccount->invoices()
                        ->where('status', '!=', 'void')
                        ->get(['balance_due'])
                        ->sum(
                            fn (Invoice $invoice): int => $this->toMinorUnits(
                                $invoice->balance_due
                            )
                        );
                    $creditLimitMinor = $this->toMinorUnits(
                        $corporateAccount->credit_limit
                    );

                    if ($outstandingMinor + $totalMinor > $creditLimitMinor) {
                        throw ValidationException::withMessages([
                            'corporate_account_uuid' => [
                                'This invoice would exceed the corporate account credit limit.',
                            ],
                        ]);
                    }
                }

                $dueAt = filled($data['due_at'] ?? null)
                    ? Carbon::parse($data['due_at'])
                    : (
                        $corporateAccount === null
                            ? now()
                            : now()->addDays(
                                $corporateAccount->payment_terms_days
                            )
                    );

                $invoice = Invoice::query()->create([
                    'invoice_number' => $this->numbers->invoice(),
                    'laboratory_order_id' => $order->getKey(),
                    'patient_id' => $order->patient_id,
                    'branch_id' => $branch->getKey(),
                    'corporate_account_id' => $corporateAccount?->getKey(),
                    'status' => 'draft',
                    'currency' => $order->priceList?->currency ?? 'NGN',
                    'subtotal' => $this->formatMinorUnits($subtotalMinor),
                    'discount_amount' => $this->formatMinorUnits($discountMinor),
                    'tax_amount' => $this->formatMinorUnits($taxMinor),
                    'total_amount' => $this->formatMinorUnits($totalMinor),
                    'amount_paid' => '0.00',
                    'balance_due' => $this->formatMinorUnits($totalMinor),
                    'notes' => $data['notes'] ?? null,
                    'due_at' => $dueAt,
                    'created_by' => $actor->getKey(),
                    'updated_by' => $actor->getKey(),
                ]);

                foreach ($order->items as $item) {
                    $invoice->items()->create([
                        'laboratory_order_item_id' => $item->getKey(),
                        'description' => $item->test_name_snapshot,
                        'quantity' => '1.00',
                        'unit_price' => $item->price_snapshot,
                        'line_total' => $item->price_snapshot,
                    ]);
                }

                return $invoice->fresh([
                    'order',
                    'patient',
                    'branch',
                    'corporateAccount',
                    'items',
                    'payments.receivedBy',
                ]);
            }
        );
    }

    public function issue(Invoice $invoice, TenantUser $actor): Invoice
    {
        return DB::connection('tenant')->transaction(
            function () use ($invoice, $actor): Invoice {
                $lockedInvoice = Invoice::query()
                    ->whereKey($invoice->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedInvoice->status !== 'draft') {
                    throw ValidationException::withMessages([
                        'invoice' => ['Only a draft invoice can be issued.'],
                    ]);
                }

                $isPaid = $this->toMinorUnits(
                    $lockedInvoice->balance_due
                ) === 0;

                $lockedInvoice->forceFill([
                    'status' => $isPaid ? 'paid' : 'issued',
                    'issued_at' => now(),
                    'paid_at' => $isPaid ? now() : null,
                    'updated_by' => $actor->getKey(),
                ])->save();

                return $this->loadInvoice($lockedInvoice);
            }
        );
    }

    public function void(Invoice $invoice, TenantUser $actor): Invoice
    {
        return DB::connection('tenant')->transaction(
            function () use ($invoice, $actor): Invoice {
                $lockedInvoice = Invoice::query()
                    ->whereKey($invoice->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($this->toMinorUnits($lockedInvoice->amount_paid) > 0) {
                    throw ValidationException::withMessages([
                        'invoice' => [
                            'An invoice with recorded payments cannot be voided.',
                        ],
                    ]);
                }

                if ($lockedInvoice->status === 'paid') {
                    throw ValidationException::withMessages([
                        'invoice' => ['A paid invoice cannot be voided.'],
                    ]);
                }

                if ($lockedInvoice->status === 'void') {
                    throw ValidationException::withMessages([
                        'invoice' => ['The invoice is already void.'],
                    ]);
                }

                $lockedInvoice->forceFill([
                    'status' => 'void',
                    'voided_at' => now(),
                    'updated_by' => $actor->getKey(),
                ])->save();

                return $this->loadInvoice($lockedInvoice);
            }
        );
    }

    private function ensureOrderBranchAccess(
        LaboratoryOrder $order,
        TenantUser $actor
    ): void {
        if ($actor->isAdmin()) {
            return;
        }

        $assignedBranchIds = $actor->branches()
            ->pluck('branches.id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if (! in_array(
            (int) $order->collection_branch_id,
            $assignedBranchIds,
            true
        )) {
            abort(404);
        }
    }

    private function loadInvoice(Invoice $invoice): Invoice
    {
        return $invoice->fresh([
            'order',
            'patient',
            'branch',
            'corporateAccount',
            'items',
            'payments.receivedBy',
        ]);
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
