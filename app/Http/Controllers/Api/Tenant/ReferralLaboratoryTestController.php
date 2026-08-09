<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesReferrals;
use App\Http\Controllers\Controller;
use App\Http\Requests\Referral\IndexReferralLaboratoryTestRequest;
use App\Http\Requests\Referral\StoreReferralLaboratoryTestRequest;
use App\Http\Resources\ReferralLaboratoryTestResource;
use App\Models\ReferralLaboratory;
use App\Models\ReferralLaboratoryTest;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Referral\ReferralLaboratoryService;
use App\Services\Tenant\Referral\ReferralQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReferralLaboratoryTestController extends Controller
{
    use AuthorizesReferrals;

    public function __construct(
        protected ReferralQueryService $queries,
        protected ReferralLaboratoryService $laboratories,
        protected EntitlementService $entitlements
    ) {}

    public function index(
        IndexReferralLaboratoryTestRequest $request,
        ReferralLaboratory $laboratory
    ): AnonymousResourceCollection {
        $this->ensureReferralEntitlement($this->entitlements);
        $data = $request->validated();

        return ReferralLaboratoryTestResource::collection(
            $this->queries->mappings(
                $laboratory,
                $data['search'] ?? null,
                array_key_exists('active', $data)
                    ? (bool) $data['active']
                    : null,
                $data['lab_test_uuid'] ?? null,
                (int) ($data['per_page'] ?? 50)
            )
        );
    }

    public function store(
        StoreReferralLaboratoryTestRequest $request,
        ReferralLaboratory $laboratory
    ): JsonResponse {
        $this->ensureReferralEntitlement($this->entitlements);

        return (new ReferralLaboratoryTestResource(
            $this->laboratories->saveTestMapping(
                $laboratory,
                null,
                $request->validated(),
                $this->referralUser($request)
            )
        ))->additional([
            'message' => 'Referral laboratory test mapping created successfully.',
        ])->response()->setStatusCode(201);
    }

    public function update(
        StoreReferralLaboratoryTestRequest $request,
        ReferralLaboratoryTest $mapping
    ): JsonResponse {
        $this->ensureReferralEntitlement($this->entitlements);
        $mapping->loadMissing('referralLaboratory');

        return (new ReferralLaboratoryTestResource(
            $this->laboratories->saveTestMapping(
                $mapping->referralLaboratory,
                $mapping,
                $request->validated(),
                $this->referralUser($request)
            )
        ))->additional([
            'message' => 'Referral laboratory test mapping updated successfully.',
        ])->response();
    }
}
