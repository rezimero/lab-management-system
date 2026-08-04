<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesInventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\IndexInventoryStockRequest;
use App\Http\Requests\Inventory\StoreInventoryAdjustmentRequest;
use App\Http\Resources\InventoryAdjustmentResource;
use App\Models\InventoryAdjustment;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Inventory\InventoryAdjustmentService;
use App\Services\Tenant\Inventory\InventoryQueryService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryAdjustmentController extends Controller
{
    use AuthorizesInventory;

    public function __construct(
        protected InventoryQueryService $queries,
        protected InventoryAdjustmentService $adjustments,
        protected EntitlementService $entitlements
    ) {}

    public function index(IndexInventoryStockRequest $request): AnonymousResourceCollection
    {
        $this->ensureInventoryEntitlement($this->entitlements);
        $data = $request->validated();

        return InventoryAdjustmentResource::collection($this->queries->adjustments(
            $this->inventoryUser($request),
            $data,
            (int) ($data['per_page'] ?? 25)
        ));
    }

    public function store(StoreInventoryAdjustmentRequest $request): JsonResponse
    {
        $this->ensureInventoryEntitlement($this->entitlements);

        return (new InventoryAdjustmentResource($this->adjustments->post(
            $request->validated(),
            $this->inventoryUser($request)
        )))->additional(['message' => 'Inventory adjustment posted successfully.'])
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, InventoryAdjustment $adjustment): InventoryAdjustmentResource
    {
        $this->ensureInventoryEntitlement($this->entitlements);
        $user = $this->inventoryUser($request);
        $this->ensureInventoryPermission($user, TenantUserPermissionCatalog::VIEW_INVENTORY);

        return new InventoryAdjustmentResource($this->queries->adjustment($user, $adjustment));
    }
}
