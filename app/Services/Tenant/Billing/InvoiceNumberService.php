<?php

namespace App\Services\Tenant\Billing;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Str;
use RuntimeException;

class InvoiceNumberService
{
    public function invoice(): string
    {
        return $this->unique(
            'INV',
            static fn (string $value): bool => Invoice::query()
                ->where('invoice_number', $value)
                ->exists()
        );
    }

    public function payment(): string
    {
        return $this->unique(
            'PAY',
            static fn (string $value): bool => Payment::query()
                ->where('payment_number', $value)
                ->exists()
        );
    }

    public function receipt(): string
    {
        return $this->unique(
            'RCP',
            static fn (string $value): bool => Payment::query()
                ->where('receipt_number', $value)
                ->exists()
        );
    }

    private function unique(string $prefix, callable $exists): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $value = sprintf(
                '%s-%s-%s',
                $prefix,
                now()->format('Ymd'),
                strtoupper(substr(str_replace('-', '', (string) Str::uuid()), 0, 8))
            );

            if (! $exists($value)) {
                return $value;
            }
        }

        throw new RuntimeException(
            "Unable to generate a unique {$prefix} number."
        );
    }
}
