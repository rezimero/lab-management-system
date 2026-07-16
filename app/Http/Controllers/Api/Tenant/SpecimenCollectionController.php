<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Specimen\CollectSpecimensRequest;
use App\Http\Resources\LaboratoryOrderResource;
use App\Services\Tenant\Specimen\SpecimenCollectionService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SpecimenCollectionController extends Controller
{
    public function __construct(
        protected SpecimenCollectionService $collectionService
    ) {
    }

    /**
     * Collect specimens for a laboratory order.
     */
    public function store(
        CollectSpecimensRequest $request,
        string $laboratoryOrder
    ): JsonResponse {
        $authenticatedId = $request
            ->user()
            ?->getAuthIdentifier();

        $order = $this->collectionService->collect(
            $laboratoryOrder,
            $request->validated(),
            is_numeric($authenticatedId)
                ? (int) $authenticatedId
                : null
        );

        return (new LaboratoryOrderResource(
            $order
        ))
            ->additional([
                'message' => (
                    'Specimens collected successfully.'
                ),
            ])
            ->response()
            ->setStatusCode(
                Response::HTTP_CREATED
            );
    }
}
