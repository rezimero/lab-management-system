<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\StoreDepartmentRequest;
use App\Http\Requests\Department\UpdateDepartmentRequest;
use App\Models\Department;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly DepartmentService $departmentService
    ) {
    }

    /**
     * Display a listing of departments.
     */
    public function index(Request $request): JsonResponse
    {
        $departments = $this->departmentService->paginate(
            search: $request->string('search')->toString(),
            activeOnly: $request->boolean('active_only'),
            perPage: (int) $request->input('per_page', 15),
        );

        return response()->json([
            'success' => true,
            'message' => 'Departments retrieved successfully.',
            'data' => $departments,
        ]);
    }

    /**
     * Store a newly created department.
     */
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = $this->departmentService->create(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Department created successfully.',
            'data' => $department,
        ], 201);
    }

    /**
     * Display the specified department.
     */
    public function show(Department $department): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Department retrieved successfully.',
            'data' => $department,
        ]);
    }

    /**
     * Update the specified department.
     */
    public function update(
        UpdateDepartmentRequest $request,
        Department $department
    ): JsonResponse {
        $department = $this->departmentService->update(
            $department,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Department updated successfully.',
            'data' => $department,
        ]);
    }

    /**
     * Remove the specified department.
     */
    public function destroy(Department $department): JsonResponse
    {
        $this->departmentService->delete($department);

        return response()->json([
            'success' => true,
            'message' => 'Department deleted successfully.',
        ]);
    }

    /**
     * Activate department.
     */
    public function activate(Department $department): JsonResponse
    {
        $department = $this->departmentService->activate($department);

        return response()->json([
            'success' => true,
            'message' => 'Department activated successfully.',
            'data' => $department,
        ]);
    }

    /**
     * Deactivate department.
     */
    public function deactivate(Department $department): JsonResponse
    {
        $department = $this->departmentService->deactivate($department);

        return response()->json([
            'success' => true,
            'message' => 'Department deactivated successfully.',
            'data' => $department,
        ]);
    }

    /**
     * Update department sort order.
     */
    public function updateSortOrder(
        Request $request,
        Department $department
    ): JsonResponse {
        $validated = $request->validate([
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $department = $this->departmentService->updateSortOrder(
            $department,
            $validated['sort_order']
        );

        return response()->json([
            'success' => true,
            'message' => 'Department sort order updated successfully.',
            'data' => $department,
        ]);
    }
}