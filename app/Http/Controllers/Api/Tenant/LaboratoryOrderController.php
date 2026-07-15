<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\LaboratoryOrder\StoreLaboratoryOrderRequest;
use App\Http\Resources\LaboratoryOrderResource;
use App\Services\Tenant\LaboratoryOrder\LaboratoryOrderCreationService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LaboratoryOrderController extends Controller
{
    public function __construct(
        protected LaboratoryOrderCreationService $creationService,
    ) {
    }

    /**
     * Create a laboratory order and its test items.
     */
    public function store(
        StoreLaboratoryOrderRequest $request
    ): JsonResponse {
        $order = $this->creationService->create(
            $request->validated()
        );

        return (new LaboratoryOrderResource(
            $order
        ))
            ->additional([
                'message' => (
                    'Laboratory order created successfully.'
                ),
            ])
            ->response()
            ->setStatusCode(
                Response::HTTP_CREATED
            );
    }
}
