<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Subscription\StorePlanRequest;
use App\Http\Requests\Landlord\Subscription\UpdatePlanRequest;
use App\Models\Plan;
use App\Services\Landlord\Subscription\SubscriptionManagementService;
use Illuminate\Http\JsonResponse;

class PlanController extends Controller
{
    public function __construct(
        protected SubscriptionManagementService $service,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Plan::query()
                ->with('features')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        return response()->json([
            'message' => 'Plan created successfully.',
            'data' => $this->service->savePlan(null, $request->validated()),
        ], 201);
    }

    public function update(
        UpdatePlanRequest $request,
        Plan $plan
    ): JsonResponse {
        return response()->json([
            'message' => 'Plan updated successfully.',
            'data' => $this->service->savePlan($plan, $request->validated()),
        ]);
    }
}
