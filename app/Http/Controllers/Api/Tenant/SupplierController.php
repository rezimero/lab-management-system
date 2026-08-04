<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesInventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\IndexInventoryLookupRequest;
use App\Http\Requests\Inventory\StoreSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Inventory\InventoryQueryService;
use App\Services\Tenant\Inventory\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupplierController extends Controller
{
    use AuthorizesInventory;

    public function __construct(
        protected InventoryQueryService $queries,
        protected SupplierService $suppliers,
        protected EntitlementService $entitlements
    ) {}

    public function index(IndexInventoryLookupRequest $request): AnonymousResourceCollection
    {
        $this->ensureInventoryEntitlement($this->entitlements);
        $data = $request->validated();

        return SupplierResource::collection($this->queries->suppliers(
            $data['search'] ?? null,
            array_key_exists('active', $data) ? (bool) $data['active'] : null,
            (int) ($data['per_page'] ?? 50)
        ));
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $this->ensureInventoryEntitlement($this->entitlements);

        return (new SupplierResource($this->suppliers->save(
            null,
            $request->validated(),
            $this->inventoryUser($request)
        )))->additional(['message' => 'Supplier created successfully.'])
            ->response()->setStatusCode(201);
    }

    public function update(StoreSupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $this->ensureInventoryEntitlement($this->entitlements);

        return (new SupplierResource($this->suppliers->save(
            $supplier,
            $request->validated(),
            $this->inventoryUser($request)
        )))->additional(['message' => 'Supplier updated successfully.'])->response();
    }
}
