<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesInventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\IndexInventoryStockRequest;
use App\Http\Requests\Inventory\StoreInventoryReceiptRequest;
use App\Http\Resources\InventoryReceiptResource;
use App\Models\InventoryReceipt;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Inventory\InventoryQueryService;
use App\Services\Tenant\Inventory\InventoryReceiptService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryReceiptController extends Controller
{
    use AuthorizesInventory;

    public function __construct(
        protected InventoryQueryService $queries,
        protected InventoryReceiptService $receipts,
        protected EntitlementService $entitlements
    ) {}

    public function index(IndexInventoryStockRequest $request): AnonymousResourceCollection
    {
        $this->ensureInventoryEntitlement($this->entitlements);
        $data = $request->validated();

        return InventoryReceiptResource::collection($this->queries->receipts(
            $this->inventoryUser($request),
            $data,
            (int) ($data['per_page'] ?? 25)
        ));
    }

    public function store(StoreInventoryReceiptRequest $request): JsonResponse
    {
        $this->ensureInventoryEntitlement($this->entitlements);

        return (new InventoryReceiptResource($this->receipts->post(
            $request->validated(),
            $this->inventoryUser($request)
        )))->additional(['message' => 'Inventory receipt posted successfully.'])
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, InventoryReceipt $receipt): InventoryReceiptResource
    {
        $this->ensureInventoryEntitlement($this->entitlements);
        $user = $this->inventoryUser($request);
        $this->ensureInventoryPermission($user, TenantUserPermissionCatalog::VIEW_INVENTORY);

        return new InventoryReceiptResource($this->queries->receipt($user, $receipt));
    }
}
