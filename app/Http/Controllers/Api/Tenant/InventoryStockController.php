<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesInventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\IndexInventoryStockRequest;
use App\Http\Resources\InventoryItemResource;
use App\Http\Resources\InventoryMovementResource;
use App\Http\Resources\InventoryStockBalanceResource;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Inventory\InventoryQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InventoryStockController extends Controller
{
    use AuthorizesInventory;

    public function __construct(
        protected InventoryQueryService $queries,
        protected EntitlementService $entitlements
    ) {}

    public function index(IndexInventoryStockRequest $request): AnonymousResourceCollection
    {
        $this->ensureInventoryEntitlement($this->entitlements);
        $data = $request->validated();

        return InventoryStockBalanceResource::collection($this->queries->stock(
            $this->inventoryUser($request),
            $data,
            (int) ($data['per_page'] ?? 25)
        ));
    }

    public function alerts(IndexInventoryStockRequest $request): JsonResponse
    {
        $this->ensureInventoryEntitlement($this->entitlements);
        $alerts = $this->queries->alerts(
            $this->inventoryUser($request),
            (int) ($request->validated()['expiry_days'] ?? 30)
        );

        return response()->json([
            'data' => [
                'summary' => $alerts['summary'],
                'low_stock' => collect($alerts['low_stock'])->map(fn ($row): array => [
                    'branch' => [
                        'uuid' => $row['branch']->uuid,
                        'code' => $row['branch']->code,
                        'name' => $row['branch']->name,
                    ],
                    'item' => (new InventoryItemResource($row['item']))->resolve($request),
                    'quantity_on_hand' => (string) $row['quantity_on_hand'],
                    'reorder_level' => (string) $row['reorder_level'],
                ])->values(),
                'out_of_stock' => collect($alerts['out_of_stock'])->map(fn ($row): array => [
                    'branch' => [
                        'uuid' => $row['branch']->uuid,
                        'code' => $row['branch']->code,
                        'name' => $row['branch']->name,
                    ],
                    'item' => (new InventoryItemResource($row['item']))->resolve($request),
                    'quantity_on_hand' => (string) $row['quantity_on_hand'],
                    'reorder_level' => (string) $row['reorder_level'],
                ])->values(),
                'expiring' => InventoryStockBalanceResource::collection($alerts['expiring'])->resolve($request),
            ],
        ]);
    }

    public function movements(IndexInventoryStockRequest $request): AnonymousResourceCollection
    {
        $this->ensureInventoryEntitlement($this->entitlements);
        $data = $request->validated();

        return InventoryMovementResource::collection($this->queries->movements(
            $this->inventoryUser($request),
            $data,
            (int) ($data['per_page'] ?? 25)
        ));
    }
}
