<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesReferrals;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referral\StoreLaboratoryReferralResultRequest;
use App\Http\Resources\LaboratoryReferralResource;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Referral\ReferralExternalResultService;
use Illuminate\Http\JsonResponse;

class LaboratoryReferralResultController extends Controller
{
    use AuthorizesReferrals;

    public function __construct(
        private readonly ReferralExternalResultService $results,
        private readonly EntitlementService $entitlements
    ) {}

    public function store(
        StoreLaboratoryReferralResultRequest $request,
        string $referral
    ): JsonResponse {
        $this->ensureReferralEntitlement($this->entitlements);

        return (new LaboratoryReferralResource(
            $this->results->capture(
                $referral,
                $request->validated(),
                $this->referralUser($request)
            )
        ))->additional([
            'message' => 'External referral result captured successfully.',
        ])->response();
    }
}
