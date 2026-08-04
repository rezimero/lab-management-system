<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesReferrals;
use App\Http\Controllers\Controller;
use App\Http\Resources\LaboratoryReferralResource;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Referral\ReferralQueryService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LaboratoryOrderItemReferralController extends Controller
{
    use AuthorizesReferrals;

    public function __construct(
        protected ReferralQueryService $queries,
        protected EntitlementService $entitlements
    ) {}

    public function index(
        Request $request,
        string $laboratoryOrderItem
    ): AnonymousResourceCollection {
        $user = $this->referralUser($request);
        $this->ensureReferralEntitlement($this->entitlements);

        if (! $user->hasPermission(
            TenantUserPermissionCatalog::VIEW_REFERRALS
        )) {
            abort(403, 'Unauthorized.');
        }

        return LaboratoryReferralResource::collection(
            $this->queries->orderItemReferrals(
                $laboratoryOrderItem,
                $user
            )
        );
    }
}
