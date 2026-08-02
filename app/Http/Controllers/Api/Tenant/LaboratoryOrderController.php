<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\LaboratoryOrder\IndexLaboratoryOrderRequest;
use App\Http\Requests\LaboratoryOrder\StoreLaboratoryOrderRequest;
use App\Http\Requests\LaboratoryOrder\UpdateLaboratoryOrderStatusRequest;
use App\Http\Resources\LaboratoryOrderResource;
use App\Models\Tenant\User as TenantUser;
use App\Services\Tenant\LaboratoryOrder\LaboratoryOrderCreationService;
use App\Services\Tenant\LaboratoryOrder\LaboratoryOrderQueryService;
use App\Services\Tenant\LaboratoryOrder\LaboratoryOrderStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class LaboratoryOrderController extends Controller
{
    public function __construct(
        protected LaboratoryOrderQueryService $queryService,
        protected LaboratoryOrderCreationService $creationService,
        protected LaboratoryOrderStatusService $statusService,
    ) {}

    public function index(
        IndexLaboratoryOrderRequest $request
    ): AnonymousResourceCollection {
        $validated = $request->validated();

        return LaboratoryOrderResource::collection(
            $this->queryService->paginate(
                search: $validated['search'] ?? null,
                status: $validated['status'] ?? null,
                priority: $validated['priority'] ?? null,
                patientId: isset($validated['patient_id'])
                    ? (int) $validated['patient_id']
                    : null,
                dateFrom: $validated['date_from'] ?? null,
                dateTo: $validated['date_to'] ?? null,
                branchUuid: $validated['branch_uuid'] ?? null,
                user: $this->user($request),
                perPage: (int) ($validated['per_page'] ?? 15),
            )
        );
    }

    public function store(
        StoreLaboratoryOrderRequest $request
    ): JsonResponse {
        $order = $this->creationService->create(
            $request->validated(),
            $this->user($request)
        );

        return (new LaboratoryOrderResource($order))
            ->additional([
                'message' => 'Laboratory order created successfully.',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function updateStatus(
        UpdateLaboratoryOrderStatusRequest $request,
        string $laboratoryOrder
    ): JsonResponse {
        $validated = $request->validated();

        $order = $this->statusService->transition(
            $laboratoryOrder,
            $validated['status']
        );

        return (new LaboratoryOrderResource($order))
            ->additional([
                'message' => 'Laboratory order status updated successfully.',
            ])
            ->response();
    }

    public function show(
        Request $request,
        string $laboratoryOrder
    ): LaboratoryOrderResource {
        return new LaboratoryOrderResource(
            $this->queryService->find(
                $laboratoryOrder,
                $this->user($request)
            )
        );
    }

    private function user(Request $request): TenantUser
    {
        $user = $request->user();

        if (! $user instanceof TenantUser) {
            abort(401, 'Unauthenticated.');
        }

        return $user;
    }
}
