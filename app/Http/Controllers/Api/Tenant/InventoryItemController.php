<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesInventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\IndexInventoryItemRequest;
use App\Http\Requests\Inventory\StoreInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Models\InventoryItem;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Inventory\InventoryCatalogService;
use App\Services\Tenant\Inventory\InventoryQueryService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryItemController extends Controller
{
    use AuthorizesInventory;

    public function __construct(
        protected InventoryQueryService $queries,
        protected InventoryCatalogService $catalog,
        protected EntitlementService $entitlements
    ) {}

    public function index(IndexInventoryItemRequest $request): AnonymousResourceCollection
    {
        $this->ensureInventoryEntitlement($this->entitlements);
        $data = $request->validated();

        return InventoryItemResource::collection($this->queries->items(
            $this->inventoryUser($request),
            $data,
            (int) ($data['per_page'] ?? 25)
        ));
    }

    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $this->ensureInventoryEntitlement($this->entitlements);

        return (new InventoryItemResource($this->catalog->saveItem(
            null,
            $request->validated(),
            $this->inventoryUser($request)
        )))->additional(['message' => 'Inventory item created successfully.'])
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, InventoryItem $item): InventoryItemResource
    {
        $this->ensureInventoryEntitlement($this->entitlements);
        $user = $this->inventoryUser($request);
        $this->ensureInventoryPermission($user, TenantUserPermissionCatalog::VIEW_INVENTORY);

        return new InventoryItemResource($this->queries->item($user, $item));
    }

    public function update(StoreInventoryItemRequest $request, InventoryItem $item): JsonResponse
    {
        $this->ensureInventoryEntitlement($this->entitlements);

        return (new InventoryItemResource($this->catalog->saveItem(
            $item,
            $request->validated(),
            $this->inventoryUser($request)
        )))->additional(['message' => 'Inventory item updated successfully.'])->response();
    }
}
