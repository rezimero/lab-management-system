<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesInventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\IndexInventoryLookupRequest;
use App\Http\Requests\Inventory\StoreInventoryCategoryRequest;
use App\Http\Resources\InventoryCategoryResource;
use App\Models\InventoryCategory;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Inventory\InventoryCatalogService;
use App\Services\Tenant\Inventory\InventoryQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryCategoryController extends Controller
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

        return InventoryCategoryResource::collection($this->queries->categories(
            $data['search'] ?? null,
            array_key_exists('active', $data) ? (bool) $data['active'] : null,
            (int) ($data['per_page'] ?? 50)
        ));
    }

    public function store(StoreInventoryCategoryRequest $request): JsonResponse
    {
        $this->ensureInventoryEntitlement($this->entitlements);

        return (new InventoryCategoryResource($this->catalog->saveCategory(
            null,
            $request->validated(),
            $this->inventoryUser($request)
        )))->additional(['message' => 'Inventory category created successfully.'])
            ->response()->setStatusCode(201);
    }

    public function update(
        StoreInventoryCategoryRequest $request,
        InventoryCategory $category
    ): JsonResponse {
        $this->ensureInventoryEntitlement($this->entitlements);

        return (new InventoryCategoryResource($this->catalog->saveCategory(
            $category,
            $request->validated(),
            $this->inventoryUser($request)
        )))->additional(['message' => 'Inventory category updated successfully.'])->response();
    }
}
