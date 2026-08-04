<?php

namespace App\Services\Tenant\Referral;

use App\Models\ReferralLaboratory;
use App\Models\ReferralLaboratoryTest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReferralQueryService
{
    public function laboratories(
        ?string $search,
        ?bool $active,
        int $perPage
    ): LengthAwarePaginator {
        return ReferralLaboratory::query()
            ->withCount(['testMappings', 'referrals'])
            ->when(
                $search,
                fn ($query, $value) => $query->where(
                    fn ($query) => $query
                        ->where('name', 'like', "%{$value}%")
                        ->orWhere('code', 'like', "%{$value}%")
                        ->orWhere(
                            'accreditation_number',
                            'like',
                            "%{$value}%"
                        )
                        ->orWhere('city', 'like', "%{$value}%")
                        ->orWhere('state', 'like', "%{$value}%")
                )
            )
            ->when(
                $active !== null,
                fn ($query) => $query->where('is_active', $active)
            )
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function laboratory(
        ReferralLaboratory $laboratory
    ): ReferralLaboratory {
        return $laboratory->load([
            'testMappings' => fn ($query) => $query
                ->with('labTest')
                ->orderBy('external_test_name'),
        ])->loadCount(['testMappings', 'referrals']);
    }

    public function mappings(
        ReferralLaboratory $laboratory,
        ?string $search,
        ?bool $active,
        ?string $labTestUuid,
        int $perPage
    ): LengthAwarePaginator {
        return ReferralLaboratoryTest::query()
            ->with(['referralLaboratory', 'labTest'])
            ->where(
                'referral_laboratory_id',
                $laboratory->getKey()
            )
            ->when(
                $search,
                fn ($query, $value) => $query->where(
                    fn ($query) => $query
                        ->where(
                            'external_test_code',
                            'like',
                            "%{$value}%"
                        )
                        ->orWhere(
                            'external_test_name',
                            'like',
                            "%{$value}%"
                        )
                        ->orWhereHas(
                            'labTest',
                            fn ($labTest) => $labTest
                                ->where('name', 'like', "%{$value}%")
                                ->orWhere(
                                    'category',
                                    'like',
                                    "%{$value}%"
                                )
                        )
                )
            )
            ->when(
                $active !== null,
                fn ($query) => $query->where('is_active', $active)
            )
            ->when(
                $labTestUuid,
                fn ($query, $value) => $query->whereHas(
                    'labTest',
                    fn ($labTest) => $labTest->where('uuid', $value)
                )
            )
            ->orderBy('external_test_name')
            ->paginate($perPage);
    }
}
