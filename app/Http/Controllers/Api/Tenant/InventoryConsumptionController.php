<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesInventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreInventoryConsumptionRequest;
use App\Http\Resources\InventoryConsumptionResource;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\InventoryStockBalanceResource;
use App\Models\InventoryConsumption;
use App\Models\LaboratoryOrderItem;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Inventory\InventoryConsumptionService;
use App\Services\Tenant\Inventory\InventoryQueryService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryConsumptionController extends Controller
{
    use AuthorizesInventory;

    public function __construct(
        protected InventoryQueryService $queries,
        protected InventoryConsumptionService $consumptions,
        protected EntitlementService $entitlements
    ) {}

    public function index(Request $request, LaboratoryOrderItem $laboratoryOrderItem): JsonResponse
    {
        $this->ensureInventoryEntitlement($this->entitlements);
        $user = $this->inventoryUser($request);
        $this->ensureInventoryPermission($user, TenantUserPermissionCatalog::VIEW_INVENTORY);
        $context = $this->queries->orderItemConsumptionContext($user, $laboratoryOrderItem);

        return response()->json(['data' => [
            'order_item' => [
                'uuid' => $context['order_item']->uuid,
                'test_name' => $context['order_item']->test_name_snapshot,
                'status' => $context['order_item']->status,
            ],
            'branch' => [
                'uuid' => $context['branch']->uuid,
                'code' => $context['branch']->code,
                'name' => $context['branch']->name,
            ],
            'suggestions' => $context['suggestions']->map(fn ($requirement): array => [
                'quantity' => (string) $requirement->quantity,
                'notes' => $requirement->notes,
                'item' => (new InventoryItemResource($requirement->item))->resolve($request),
            ])->values(),
            'available_lots' => InventoryStockBalanceResource::collection($context['available_lots'])->resolve($request),
            'consumptions' => InventoryConsumptionResource::collection($context['consumptions'])->resolve($request),
        ]]);
    }

    public function store(
        StoreInventoryConsumptionRequest $request,
        LaboratoryOrderItem $laboratoryOrderItem
    ): JsonResponse {
        $this->ensureInventoryEntitlement($this->entitlements);

        return (new InventoryConsumptionResource($this->consumptions->post(
            $laboratoryOrderItem,
            $request->validated(),
            $this->inventoryUser($request)
        )))->additional(['message' => 'Inventory consumption posted successfully.'])
            ->response()->setStatusCode(201);
    }

    public function show(Request $request, InventoryConsumption $consumption): InventoryConsumptionResource
    {
        $this->ensureInventoryEntitlement($this->entitlements);
        $user = $this->inventoryUser($request);
        $this->ensureInventoryPermission($user, TenantUserPermissionCatalog::VIEW_INVENTORY);

        return new InventoryConsumptionResource($this->queries->consumption($user, $consumption));
    }
}
