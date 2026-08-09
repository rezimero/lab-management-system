<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesReferrals;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referral\IndexLaboratoryReferralRequest;
use App\Http\Requests\Referral\StoreLaboratoryReferralRequest;
use App\Http\Resources\LaboratoryReferralResource;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Referral\ReferralCreationService;
use App\Services\Tenant\Referral\ReferralQueryService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LaboratoryReferralController extends Controller
{
    use AuthorizesReferrals;

    public function __construct(
        protected ReferralQueryService $queries,
        protected ReferralCreationService $creation,
        protected EntitlementService $entitlements
    ) {}

    public function index(
        IndexLaboratoryReferralRequest $request
    ): AnonymousResourceCollection {
        $this->ensureReferralEntitlement($this->entitlements);

        return LaboratoryReferralResource::collection(
            $this->queries->referrals(
                $request->validated(),
                $this->referralUser($request)
            )
        );
    }

    public function summary(
        IndexLaboratoryReferralRequest $request
    ): JsonResponse {
        $this->ensureReferralEntitlement($this->entitlements);

        return response()->json([
            'data' => $this->queries->summary(
                $request->safe()->except('per_page'),
                $this->referralUser($request)
            ),
        ]);
    }

    public function store(
        StoreLaboratoryReferralRequest $request
    ): JsonResponse {
        $this->ensureReferralEntitlement($this->entitlements);

        return (new LaboratoryReferralResource(
            $this->creation->create(
                $request->validated(),
                $this->referralUser($request)
            )
        ))->additional([
            'message' => 'Laboratory referral created successfully.',
        ])->response()->setStatusCode(201);
    }

    public function show(
        Request $request,
        string $referral
    ): LaboratoryReferralResource {
        $user = $this->referralUser($request);
        $this->ensureReferralEntitlement($this->entitlements);

        if (! $user->hasPermission(
            TenantUserPermissionCatalog::VIEW_REFERRALS
        )) {
            abort(403, 'Unauthorized.');
        }

        return new LaboratoryReferralResource(
            $this->queries->referral($referral, $user)
        );
    }
}
