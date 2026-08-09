<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesReferrals;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referral\IndexReferralLaboratoryRequest;
use App\Http\Requests\Referral\StoreReferralLaboratoryRequest;
use App\Http\Resources\ReferralLaboratoryResource;
use App\Models\ReferralLaboratory;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Referral\ReferralLaboratoryService;
use App\Services\Tenant\Referral\ReferralQueryService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReferralLaboratoryController extends Controller
{
    use AuthorizesReferrals;

    public function __construct(
        protected ReferralQueryService $queries,
        protected ReferralLaboratoryService $laboratories,
        protected EntitlementService $entitlements
    ) {}

    public function index(
        IndexReferralLaboratoryRequest $request
    ): AnonymousResourceCollection {
        $this->ensureReferralEntitlement($this->entitlements);
        $data = $request->validated();

        return ReferralLaboratoryResource::collection(
            $this->queries->laboratories(
                $data['search'] ?? null,
                array_key_exists('active', $data)
                    ? (bool) $data['active']
                    : null,
                (int) ($data['per_page'] ?? 50)
            )
        );
    }

    public function store(
        StoreReferralLaboratoryRequest $request
    ): JsonResponse {
        $this->ensureReferralEntitlement($this->entitlements);

        return (new ReferralLaboratoryResource(
            $this->laboratories->saveLaboratory(
                null,
                $request->validated(),
                $this->referralUser($request)
            )
        ))->additional([
            'message' => 'Referral laboratory created successfully.',
        ])->response()->setStatusCode(201);
    }

    public function show(
        Request $request,
        ReferralLaboratory $laboratory
    ): ReferralLaboratoryResource {
        $user = $this->referralUser($request);
        $this->ensureReferralEntitlement($this->entitlements);

        if (! $user->hasPermission(
            TenantUserPermissionCatalog::VIEW_REFERRALS
        )) {
            abort(403, 'Unauthorized.');
        }

        return new ReferralLaboratoryResource(
            $this->queries->laboratory($laboratory)
        );
    }

    public function update(
        StoreReferralLaboratoryRequest $request,
        ReferralLaboratory $laboratory
    ): JsonResponse {
        $this->ensureReferralEntitlement($this->entitlements);

        return (new ReferralLaboratoryResource(
            $this->laboratories->saveLaboratory(
                $laboratory,
                $request->validated(),
                $this->referralUser($request)
            )
        ))->additional([
            'message' => 'Referral laboratory updated successfully.',
        ])->response();
    }
}
