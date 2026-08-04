<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Api\Tenant\Concerns\AuthorizesInventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\SyncLabTestInventoryRequirementsRequest;
use App\Http\Resources\InventoryItemResource;
use App\Models\LabTest;
use App\Services\Tenant\EntitlementService;
use App\Services\Tenant\Inventory\InventoryQueryService;
use App\Services\Tenant\Inventory\LabTestInventoryRequirementService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabTestInventoryRequirementController extends Controller
{
    use AuthorizesInventory;

    public function __construct(
        protected InventoryQueryService $queries,
        protected LabTestInventoryRequirementService $requirements,
        protected EntitlementService $entitlements
    ) {}

    public function show(Request $request, string $labTest): JsonResponse
    {
        $this->ensureInventoryEntitlement($this->entitlements);
        $user = $this->inventoryUser($request);
        $this->ensureInventoryPermission($user, TenantUserPermissionCatalog::VIEW_INVENTORY);
        $test = $this->queries->labTestByUuid($labTest)->load('inventoryRequirements.item.category', 'inventoryRequirements.item.unit');

        return response()->json(['data' => $this->payload($test, $request)]);
    }

    public function update(
        SyncLabTestInventoryRequirementsRequest $request,
        string $labTest
    ): JsonResponse {
        $this->ensureInventoryEntitlement($this->entitlements);
        $test = $this->queries->labTestByUuid($labTest);
        $updated = $this->requirements->sync(
            $test,
            $request->validated()['requirements'],
            $this->inventoryUser($request)
        );

        return response()->json([
            'message' => 'Laboratory test inventory requirements updated successfully.',
            'data' => $this->payload($updated, $request),
        ]);
    }

    private function payload(LabTest $test, Request $request): array
    {
        return [
            'lab_test' => [
                'uuid' => $test->uuid,
                'id' => $test->getKey(),
                'name' => $test->name,
                'category' => $test->category,
            ],
            'requirements' => $test->inventoryRequirements->map(fn ($requirement): array => [
                'quantity' => (string) $requirement->quantity,
                'is_active' => (bool) $requirement->is_active,
                'notes' => $requirement->notes,
                'item' => (new InventoryItemResource($requirement->item))->resolve($request),
            ])->values(),
        ];
    }
}
