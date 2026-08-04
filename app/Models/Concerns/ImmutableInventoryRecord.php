<?php

namespace App\Models\Concerns;

use LogicException;

trait ImmutableInventoryRecord
{
    protected static function bootImmutableInventoryRecord(): void
    {
        static::updating(static function (): void {
            throw new LogicException('Posted inventory records are immutable.');
        });

        static::deleting(static function (): void {
            throw new LogicException('Posted inventory records cannot be deleted.');
        });
    }
}
