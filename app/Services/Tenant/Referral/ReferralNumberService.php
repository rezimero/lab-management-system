<?php

namespace App\Services\Tenant\Referral;

use App\Models\LaboratoryReferral;
use Illuminate\Support\Str;
use RuntimeException;

class ReferralNumberService
{
    public function generate(): string
    {
        for ($attempt = 0; $attempt < 20; $attempt++) {
            $number = sprintf(
                'REF-%s-%s',
                now()->format('Ymd'),
                Str::upper(Str::random(8))
            );

            if (! LaboratoryReferral::query()
                ->where('referral_number', $number)
                ->exists()) {
                return $number;
            }
        }

        throw new RuntimeException(
            'Unable to allocate a unique referral number.'
        );
    }
}
