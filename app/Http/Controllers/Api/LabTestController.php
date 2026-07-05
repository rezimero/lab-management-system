<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LabTest;
use Illuminate\Http\Request;

class LabTestController extends Controller
{
    /**
     * Display a listing of lab tests
     */
    public function index(Request $request)
    {
        $query = LabTest::query();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search by name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $labTests = $query->orderBy('name')->paginate(15);

        return response()->json($labTests);
    }

    /**
     * Store a newly created lab test
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'turnaround_time_hours' => 'nullable|integer|min:1',
            'parameters' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $labTest = LabTest::create($validated);

        return response()->json([
            'message' => 'Lab test created successfully',
            'lab_test' => $labTest,
        ], 201);
    }

    /**
     * Display the specified lab test
     */
    public function show(LabTest $labTest)
    {
        return response()->json($labTest);
    }

    /**
     * Update the specified lab test
     */
    public function update(Request $request, LabTest $labTest)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'turnaround_time_hours' => 'nullable|integer|min:1',
            'parameters' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $labTest->update($validated);

        return response()->json([
            'message' => 'Lab test updated successfully',
            'lab_test' => $labTest,
        ]);
    }

    /**
     * Remove the specified lab test
     */
    public function destroy(LabTest $labTest)
    {
        $labTest->delete();

        return response()->json([
            'message' => 'Lab test deleted successfully',
        ]);
    }

    /**
     * Get lab test categories
     */
    public function categories()
    {
        $categories = LabTest::distinct()->pluck('category');

        return response()->json([
            'categories' => $categories,
        ]);
    }
}