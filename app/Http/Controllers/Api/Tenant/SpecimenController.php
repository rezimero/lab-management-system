<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Specimen\IndexSpecimenRequest;
use App\Http\Resources\SpecimenResource;
use App\Services\Tenant\Specimen\SpecimenQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SpecimenController extends Controller
{
    public function __construct(
        protected SpecimenQueryService $queryService
    ) {
    }

    /**
     * Display a filtered specimen listing.
     */
    public function index(
        IndexSpecimenRequest $request
    ): AnonymousResourceCollection {
        $validated = $request->validated();

        $specimens = $this->queryService->paginate(
            search: $validated['search'] ?? null,
            status: $validated['status'] ?? null,
            orderUuid: $validated['order_uuid'] ?? null,
            specimenType: $validated['specimen_type'] ?? null,
            dateFrom: $validated['date_from'] ?? null,
            dateTo: $validated['date_to'] ?? null,
            perPage: (int) (
                $validated['per_page'] ?? 15
            ),
        );

        return SpecimenResource::collection(
            $specimens
        );
    }

    /**
     * Display tenant specimen status totals.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => $this->queryService->stats(),
        ]);
    }

    /**
     * Display a specimen using its UUID.
     */
    public function show(
        string $specimen
    ): SpecimenResource {
        return new SpecimenResource(
            $this->queryService->find(
                $specimen
            )
        );
    }
}