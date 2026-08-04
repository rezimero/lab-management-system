<?php

namespace Tests\Unit\Inventory;

use App\Support\Inventory\InventoryDecimal;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class InventoryDecimalTest extends TestCase
{
    public function test_it_adds_and_subtracts_without_floating_point_drift(): void
    {
        $this->assertSame(
            '0.300000',
            InventoryDecimal::add('0.100000', '0.200000')
        );
        $this->assertSame(
            '9.500000',
            InventoryDecimal::subtract('12.000000', '2.500000')
        );
    }

    public function test_it_normalizes_signed_values_and_compares_them(): void
    {
        $this->assertSame('-2.500000', InventoryDecimal::negate('2.5'));
        $this->assertSame(0, InventoryDecimal::compare('1', '1.000000'));
        $this->assertSame(1, InventoryDecimal::compare('1.000001', '1'));
    }

    public function test_it_rejects_precision_beyond_the_stock_scale(): void
    {
        $this->expectException(InvalidArgumentException::class);

        InventoryDecimal::normalize('1.0000001');
    }
}
