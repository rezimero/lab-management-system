<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Resources\LaboratoryOrderItemResource;
use App\Models\LaboratoryOrderItem;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\LaboratoryOrder\LaboratoryOrderItemProcessingService;
use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LaboratoryOrderItemProcessingController extends Controller
{
    public function __construct(
        private readonly LaboratoryOrderItemProcessingService $service
    ) {
    }

    /**
     * Start processing an order item.
     */
    public function store(
        Request $request,
        LaboratoryOrderItem $laboratoryOrderItem
    ): JsonResponse {
        $user = $request->user();

        abort_unless(
            $user instanceof TenantUser
            && $user->hasPermission(
                TenantUserPermissionCatalog::CREATE_TEST_RESULTS
            ),
            403
        );

        $item = $this->service->start(
            $laboratoryOrderItem->uuid,
            (int) $user->id
        );

        return (new LaboratoryOrderItemResource(
            $item
        ))
            ->additional([
                'message' => (
                    'Laboratory order item processing started successfully.'
                ),
            ])
            ->response();
    }
}
