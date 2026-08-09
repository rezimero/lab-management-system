<?php

namespace App\Models\Concerns;

use LogicException;

trait ImmutableReferralEvidence
{
    protected static function bootImmutableReferralEvidence(): void
    {
        static::updating(static function (): void {
            throw new LogicException(
                'Referral evidence is immutable after creation.'
            );
        });

        static::deleting(static function (): void {
            throw new LogicException(
                'Referral evidence cannot be deleted.'
            );
        });
    }
}
