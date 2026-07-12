<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentService $departmentService,
    ) {
    }

    /**
     * Display a paginated listing of departments.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'active_only' => [
                'nullable',
                'boolean',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $departments = $this->departmentService->paginate(
            search: $validated['search'] ?? null,
            activeOnly: $request->boolean('active_only'),
            perPage: (int) ($validated['per_page'] ?? 15),
        );

        return DepartmentResource::collection($departments);
    }

    /**
     * Store a newly created department.
     */
    public function store(
        StoreDepartmentRequest $request
    ): JsonResponse {
        $department = $this->departmentService->create(
            $request->validated()
        );

        return (new DepartmentResource($department))
            ->additional([
                'message' => 'Department created successfully.',
            ])
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display a department using its public UUID.
     */
    public function show(string $department): DepartmentResource
    {
        return new DepartmentResource(
            $this->departmentService->find($department)
        );
    }

    /**
     * Update a department using its public UUID.
     */
    public function update(
        UpdateDepartmentRequest $request,
        string $department
    ): JsonResponse {
        $departmentModel = $this->departmentService->find($department);

        $departmentModel = $this->departmentService->update(
            $departmentModel,
            $request->validated()
        );

        return (new DepartmentResource($departmentModel))
            ->additional([
                'message' => 'Department updated successfully.',
            ])
            ->response();
    }

    /**
     * Soft-delete a department using its public UUID.
     */
    public function destroy(string $department): JsonResponse
    {
        $departmentModel = $this->departmentService->find($department);

        $this->departmentService->delete($departmentModel);

        return response()->json([
            'message' => 'Department deleted successfully.',
        ]);
    }

    /**
     * Activate a department.
     */
    public function activate(string $department): JsonResponse
    {
        $departmentModel = $this->departmentService->find($department);

        $departmentModel = $this->departmentService->activate(
            $departmentModel
        );

        return (new DepartmentResource($departmentModel))
            ->additional([
                'message' => 'Department activated successfully.',
            ])
            ->response();
    }

    /**
     * Deactivate a department.
     */
    public function deactivate(string $department): JsonResponse
    {
        $departmentModel = $this->departmentService->find($department);

        $departmentModel = $this->departmentService->deactivate(
            $departmentModel
        );

        return (new DepartmentResource($departmentModel))
            ->additional([
                'message' => 'Department deactivated successfully.',
            ])
            ->response();
    }
}