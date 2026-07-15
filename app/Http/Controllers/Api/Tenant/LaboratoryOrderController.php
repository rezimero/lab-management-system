<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\LaboratoryOrder\IndexLaboratoryOrderRequest;
use App\Http\Requests\LaboratoryOrder\StoreLaboratoryOrderRequest;
use App\Http\Resources\LaboratoryOrderResource;
use App\Services\Tenant\LaboratoryOrder\LaboratoryOrderCreationService;
use App\Services\Tenant\LaboratoryOrder\LaboratoryOrderQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class LaboratoryOrderController extends Controller
{
    public function __construct(
        protected LaboratoryOrderQueryService $queryService,
        protected LaboratoryOrderCreationService $creationService,
    ) {
    }

    /**
     * Display a paginated tenant order listing.
     */
    public function index(
        IndexLaboratoryOrderRequest $request
    ): AnonymousResourceCollection {
        $validated = $request->validated();

        $orders = $this->queryService->paginate(
            search: $validated['search'] ?? null,
            status: $validated['status'] ?? null,
            priority: $validated['priority'] ?? null,
            patientId: isset($validated['patient_id'])
                ? (int) $validated['patient_id']
                : null,
            dateFrom: $validated['date_from'] ?? null,
            dateTo: $validated['date_to'] ?? null,
            perPage: (int) (
                $validated['per_page'] ?? 15
            ),
        );

        return LaboratoryOrderResource::collection(
            $orders
        );
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

    /**
     * Display an order using its public UUID.
     */
    public function show(
        string $laboratoryOrder
    ): LaboratoryOrderResource {
        return new LaboratoryOrderResource(
            $this->queryService->find(
                $laboratoryOrder
            )
        );
    }
}
