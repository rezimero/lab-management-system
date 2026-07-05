<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TestResult;
use App\Models\Patient;
use App\Models\LabTest;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestResultController extends Controller
{
    /**
     * Display a listing of test results
     */
    public function index(Request $request)
    {
        try {
            $query = TestResult::with(['patient', 'labTest', 'user']);

            if ($request->has('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }
            if ($request->has('lab_test_id')) {
                $query->where('lab_test_id', $request->lab_test_id);
            }
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            if ($request->has('from_date')) {
                $query->whereDate('test_date', '>=', $request->from_date);
            }
            if ($request->has('to_date')) {
                $query->whereDate('test_date', '<=', $request->to_date);
            }

            $testResults = $query->orderBy('created_at', 'desc')->paginate(15);
            return response()->json($testResults);

        } catch (\Exception $e) {
            Log::error('Error fetching test results:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created test result
     */
    public function store(Request $request)
    {
        try {
            // Ensure tenant context is set
            $tenant = Tenant::current();
            if (!$tenant) {
                Log::error('No tenant context set');
                return response()->json(['error' => 'Tenant context not set'], 500);
            }

            Log::info('Creating test result for tenant: ' . $tenant->database);

            $validated = $request->validate([
                'patient_id' => [
                    'required',
                    'integer',
                    function ($attribute, $value, $fail) use ($tenant) {
                        $exists = DB::connection('tenant')
                            ->table('patients')
                            ->where('id', $value)
                            ->exists();
                        if (!$exists) {
                            $fail('The selected patient does not exist.');
                        }
                    }
                ],
                'lab_test_id' => [
                    'required',
                    'integer',
                    function ($attribute, $value, $fail) use ($tenant) {
                        $exists = DB::connection('tenant')
                            ->table('lab_tests')
                            ->where('id', $value)
                            ->exists();
                        if (!$exists) {
                            $fail('The selected lab test does not exist.');
                        }
                    }
                ],
                'test_date' => 'required|date',
                'results_data' => 'sometimes|array',  // ← FIXED: Changed to 'sometimes'
                'notes' => 'nullable|string',
                'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
            ]);

            // Set default status if not provided
            if (!isset($validated['status'])) {
                $validated['status'] = 'pending';
            }

            // Ensure results_data is always an array
            if (!isset($validated['results_data'])) {
                $validated['results_data'] = [];
            }

            // Add the authenticated user
            $validated['user_id'] = $request->user()->id;

            Log::info('Validated data:', ['validated' => $validated]);

            $testResult = TestResult::create($validated);

            if ($validated['status'] === 'completed') {
                $testResult->completed_at = now();
                $testResult->save();
            }

            return response()->json([
                'message' => 'Test result created successfully',
                'test_result' => $testResult->load(['patient', 'labTest', 'user']),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed:', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating test result:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified test result
     */
    public function show(TestResult $testResult)
    {
        try {
            return response()->json($testResult->load(['patient', 'labTest', 'user']));
        } catch (\Exception $e) {
            Log::error('Error showing test result:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the specified test result
     */
    public function update(Request $request, TestResult $testResult)
    {
        try {
            $validated = $request->validate([
                'patient_id' => 'sometimes|exists:patients,id',
                'lab_test_id' => 'sometimes|exists:lab_tests,id',
                'test_date' => 'sometimes|date',
                'results_data' => 'sometimes|array',  // ← FIXED: Changed to 'sometimes'
                'notes' => 'nullable|string',
                'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
            ]);

            // Ensure results_data is always an array if present
            if (isset($validated['results_data']) && !is_array($validated['results_data'])) {
                $validated['results_data'] = [];
            }

            if (isset($validated['status']) && $validated['status'] === 'completed' && $testResult->status !== 'completed') {
                $validated['completed_at'] = now();
            }

            $testResult->update($validated);

            return response()->json([
                'message' => 'Test result updated successfully',
                'test_result' => $testResult->load(['patient', 'labTest', 'user']),
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating test result:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified test result
     */
    public function destroy(TestResult $testResult)
    {
        try {
            $testResult->delete();
            return response()->json(['message' => 'Test result deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting test result:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get test results for a specific patient
     */
    public function getByPatient($patientId)
    {
        try {
            $patient = Patient::findOrFail($patientId);
            $testResults = TestResult::with(['labTest', 'user'])
                ->where('patient_id', $patientId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'patient' => $patient,
                'test_results' => $testResults,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting patient results:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get test results statistics
     */
    public function stats()
    {
        try {
            $total = TestResult::count();
            $pending = TestResult::where('status', 'pending')->count();
            $inProgress = TestResult::where('status', 'in_progress')->count();
            $completed = TestResult::where('status', 'completed')->count();
            $cancelled = TestResult::where('status', 'cancelled')->count();

            return response()->json([
                'total_tests' => $total,
                'pending' => $pending,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'cancelled' => $cancelled,
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting stats:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}