<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\StorePriceListRequest;
use App\Http\Requests\Billing\UpdatePriceListRequest;
use App\Http\Resources\PriceListResource;
use App\Models\PriceList;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\Billing\PriceListService;
use App\Services\Tenant\EntitlementService;
use App\Support\Commercial\CommercialFeatureCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PriceListController extends Controller
{
    public function __construct(
        protected PriceListService $service,
        protected EntitlementService $entitlements,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->ensure($request, false);

        return PriceListResource::collection(
            PriceList::query()
                ->with('items.labTest')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->paginate(min(100, max(1, (int) $request->input('per_page', 50))))
        );
    }

    public function store(StorePriceListRequest $request): JsonResponse
    {
        $this->ensure($request, true);

        return (new PriceListResource(
            $this->service->save(null, $request->validated(), $this->user($request))
        ))->additional(['message' => 'Price list created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdatePriceListRequest $request,
        PriceList $priceList
    ): JsonResponse {
        $this->ensure($request, true);

        return (new PriceListResource(
            $this->service->save($priceList, $request->validated(), $this->user($request))
        ))->additional(['message' => 'Price list updated successfully.'])
            ->response();
    }

    private function ensure(Request $request, bool $manage): void
    {
        $tenant = Tenant::current()
            ?? abort(422, 'Tenant context is required.');
        $this->entitlements->ensure($tenant, CommercialFeatureCatalog::BILLING);
        $user = $this->user($request);

        if (! $user->hasPermission($manage ? 'manage_price_lists' : 'view_billing')) {
            abort(403, 'Unauthorized.');
        }
    }

    private function user(Request $request): TenantUser
    {
        $user = $request->user();

        return $user instanceof TenantUser
            ? $user
            : abort(401, 'Unauthenticated.');
    }
}
