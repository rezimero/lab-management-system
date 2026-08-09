<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesReferrals;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referral\AcknowledgeLaboratoryReferralRequest;
use App\Http\Requests\Referral\CancelLaboratoryReferralRequest;
use App\Http\Requests\Referral\DispatchLaboratoryReferralRequest;
use App\Http\Requests\Referral\RejectLaboratoryReferralRequest;
use App\Http\Resources\LaboratoryReferralResource;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Referral\ReferralWorkflowService;
use Illuminate\Http\JsonResponse;

class LaboratoryReferralWorkflowController extends Controller
{
    use AuthorizesReferrals;

    public function __construct(
        protected ReferralWorkflowService $workflow,
        protected EntitlementService $entitlements
    ) {}

    public function dispatch(
        DispatchLaboratoryReferralRequest $request,
        string $referral
    ): JsonResponse {
        $this->ensureReferralEntitlement($this->entitlements);

        return $this->response(
            $this->workflow->dispatch(
                $referral,
                $request->validated(),
                $this->referralUser($request)
            ),
            'Laboratory referral dispatched successfully.'
        );
    }

    public function acknowledge(
        AcknowledgeLaboratoryReferralRequest $request,
        string $referral
    ): JsonResponse {
        $this->ensureReferralEntitlement($this->entitlements);

        return $this->response(
            $this->workflow->acknowledge(
                $referral,
                $request->validated(),
                $this->referralUser($request)
            ),
            'Laboratory referral acknowledged successfully.'
        );
    }

    public function reject(
        RejectLaboratoryReferralRequest $request,
        string $referral
    ): JsonResponse {
        $this->ensureReferralEntitlement($this->entitlements);

        return $this->response(
            $this->workflow->reject(
                $referral,
                $request->validated(),
                $this->referralUser($request)
            ),
            'Laboratory referral rejected successfully.'
        );
    }

    public function cancel(
        CancelLaboratoryReferralRequest $request,
        string $referral
    ): JsonResponse {
        $this->ensureReferralEntitlement($this->entitlements);

        return $this->response(
            $this->workflow->cancel(
                $referral,
                $request->validated(),
                $this->referralUser($request)
            ),
            'Laboratory referral cancelled successfully.'
        );
    }

    private function response(
        mixed $referral,
        string $message
    ): JsonResponse {
        return (new LaboratoryReferralResource(
            $referral
        ))->additional([
            'message' => $message,
        ])->response();
    }
}
