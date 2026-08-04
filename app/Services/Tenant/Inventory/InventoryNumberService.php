<?php

namespace App\Services\Tenant\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class InventoryNumberService
{
    public function receiptNumber(): string
    {
        return $this->unique('inventory_receipts', 'receipt_number', 'IRV');
    }

    public function adjustmentNumber(): string
    {
        return $this->unique('inventory_adjustments', 'adjustment_number', 'IAD');
    }

    public function consumptionNumber(): string
    {
        return $this->unique('inventory_consumptions', 'consumption_number', 'ICN');
    }

    public function lotCode(): string
    {
        return $this->unique('inventory_lots', 'internal_lot_code', 'LOT');
    }

    private function unique(string $table, string $column, string $prefix): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $value = sprintf(
                '%s-%s-%s',
                $prefix,
                now()->format('YmdHis'),
                strtoupper(Str::random(8))
            );

            if (! DB::connection('tenant')->table($table)->where($column, $value)->exists()) {
                return $value;
            }
        }

        throw new RuntimeException('Unable to generate a unique inventory reference.');
    }
}
