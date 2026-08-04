<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesInventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\IndexInventoryLookupRequest;
use App\Http\Requests\Inventory\StoreInventoryUnitRequest;
use App\Http\Resources\InventoryUnitResource;
use App\Models\InventoryUnit;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Inventory\InventoryCatalogService;
use App\Services\Tenant\Inventory\InventoryQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryUnitController extends Controller
{
    use AuthorizesInventory;

    public function __construct(
        protected InventoryQueryService $queries,
        protected InventoryCatalogService $catalog,
        protected EntitlementService $entitlements
    ) {}

    public function index(IndexInventoryLookupRequest $request): AnonymousResourceCollection
    {
        $this->ensureInventoryEntitlement($this->entitlements);
        $data = $request->validated();

        return InventoryUnitResource::collection($this->queries->units(
            $data['search'] ?? null,
            array_key_exists('active', $data) ? (bool) $data['active'] : null,
            (int) ($data['per_page'] ?? 50)
        ));
    }

    public function store(StoreInventoryUnitRequest $request): JsonResponse
    {
        $this->ensureInventoryEntitlement($this->entitlements);

        return (new InventoryUnitResource($this->catalog->saveUnit(
            null,
            $request->validated(),
            $this->inventoryUser($request)
        )))->additional(['message' => 'Inventory unit created successfully.'])
            ->response()->setStatusCode(201);
    }

    public function update(StoreInventoryUnitRequest $request, InventoryUnit $unit): JsonResponse
    {
        $this->ensureInventoryEntitlement($this->entitlements);

        return (new InventoryUnitResource($this->catalog->saveUnit(
            $unit,
            $request->validated(),
            $this->inventoryUser($request)
        )))->additional(['message' => 'Inventory unit updated successfully.'])->response();
    }
}
