<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Http\Requests\Landlord\Subscription\AssignTenantSubscriptionRequest;
use App\Models\Landlord\User as LandlordUser;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Services\Landlord\Subscription\SubscriptionManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionManagementService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $subscriptions = TenantSubscription::query()
            ->with(['tenant', 'plan.features'])
            ->when(
                $request->input('status'),
                fn ($query, string $status) => $query->where('status', $status)
            )
            ->latest('starts_at')
            ->paginate(min(100, max(1, (int) $request->input('per_page', 50))));

        return response()->json($subscriptions);
    }

    public function assign(
        AssignTenantSubscriptionRequest $request,
        Tenant $tenant
    ): JsonResponse {
        $actor = $request->user();

        if (! $actor instanceof LandlordUser || ! $actor->isSuperAdmin()) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validated();
        $plan = Plan::query()
            ->where('uuid', $validated['plan_uuid'])
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'message' => 'Tenant subscription updated successfully.',
            'data' => $this->service->assign(
                $tenant,
                $plan,
                $validated,
                $actor
            ),
        ]);
    }
}
