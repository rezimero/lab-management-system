<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabTest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LabTestController extends Controller
{
    /**
     * Display a filtered and paginated laboratory test catalogue.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => [
                'nullable',
                'string',
                'max:100',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
            'search' => [
                'nullable',
                'string',
                'max:255',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ]);

        $search = $validated['search'] ?? null;

        $query = LabTest::query()
            ->when(
                $validated['category'] ?? null,
                fn ($query, string $category) => $query
                    ->where('category', $category)
            )
            ->when(
                array_key_exists('is_active', $validated),
                fn ($query) => $query->where(
                    'is_active',
                    $request->boolean('is_active')
                )
            )
            ->when(
                $search,
                function ($query) use ($search): void {
                    $query->where(
                        function ($query) use ($search): void {
                            $query
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'description',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'category',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->orderBy('name');

        return response()->json(
            $query->paginate(
                (int) ($validated['per_page'] ?? 15)
            )
        );
    }

    /**
     * Store a laboratory test in the current tenant catalogue.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'category' => [
                'required',
                'string',
                'max:100',
            ],
            'price' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'turnaround_time_hours' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'parameters' => [
                'nullable',
                'array',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $labTest = LabTest::query()
            ->create(
                $this->normalize($validated)
            )
            ->fresh();

        return response()->json([
            'message' => 'Lab test created successfully',
            'lab_test' => $labTest,
        ], 201);
    }

    /**
     * Display a laboratory test from the current tenant.
     */
    public function show(LabTest $labTest): JsonResponse
    {
        return response()->json($labTest);
    }

    /**
     * Update a laboratory test.
     */
    public function update(
        Request $request,
        LabTest $labTest
    ): JsonResponse {
        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'category' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'price' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
            ],
            'turnaround_time_hours' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],
            'parameters' => [
                'sometimes',
                'nullable',
                'array',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
            ],
        ]);

        $labTest->update(
            $this->normalize($validated)
        );

        return response()->json([
            'message' => 'Lab test updated successfully',
            'lab_test' => $labTest->fresh(),
        ]);
    }

    /**
     * Remove a laboratory test.
     */
    public function destroy(
        LabTest $labTest
    ): JsonResponse {
        $labTest->delete();

        return response()->json([
            'message' => 'Lab test deleted successfully',
        ]);
    }

    /**
     * Return the tenant's distinct catalogue categories.
     */
    public function categories(): JsonResponse
    {
        $categories = LabTest::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    /**
     * Normalize catalogue text fields.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        if (array_key_exists('name', $data)) {
            $data['name'] = trim($data['name']);
        }

        if (array_key_exists('category', $data)) {
            $data['category'] = trim(
                $data['category']
            );
        }

        if (
            array_key_exists('description', $data)
            && $data['description'] !== null
        ) {
            $description = trim(
                $data['description']
            );

            $data['description'] = $description === ''
                ? null
                : $description;
        }

        return $data;
    }
}