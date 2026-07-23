<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Models\TestResult;
use App\Services\Landlord\Resource\LandlordTenantResourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TestResultController extends Controller
{
    private const SEARCH_FIELDS = [
        'status',
    ];

    public function __construct(
        protected LandlordTenantResourceService $resources,
    ) {
    }

    /**
     * List all test results across all tenants (God Mode).
     */
    public function index(Request $request)
    {
        try {
            return response()->json(
                $this->resources->paginate(
                    modelClass: TestResult::class,
                    responseKey: 'test_results',
                    resourceLabel: 'test results',
                    searchFields: self::SEARCH_FIELDS,
                    search: $request->has('search')
                        && ! empty($request->search)
                            ? $request->search
                            : null,
                    perPage: $request->input(
                        'per_page',
                        15,
                    ),
                    page: $request->input('page', 1),
                )
            );
        } catch (\Exception $exception) {
            Log::error(
                'Error fetching all test results: '
                .$exception->getMessage()
            );

            return response()->json(
                ['message' => 'Failed to fetch test results'],
                500,
            );
        }
    }

    /**
     * Get a single test result from any tenant (God Mode).
     */
    public function show($id)
    {
        try {
            $testResult = $this->resources->find(
                TestResult::class,
                $id,
            );

            if ($testResult === null) {
                return response()->json(
                    ['message' => 'Test result not found'],
                    404,
                );
            }

            return response()->json($testResult);
        } catch (\Exception $exception) {
            Log::error(
                'Error finding test result: '
                .$exception->getMessage()
            );

            return response()->json(
                ['message' => 'Failed to find test result'],
                500,
            );
        }
    }

    /**
     * Delete a test result from any tenant (God Mode).
     */
    public function destroy($id)
    {
        try {
            $tenant = $this->resources->delete(
                TestResult::class,
                $id,
            );

            if ($tenant === null) {
                return response()->json(
                    ['message' => 'Test result not found'],
                    404,
                );
            }

            return response()->json([
                'message' => 'Test result deleted successfully',
                'tenant' => $tenant,
            ]);
        } catch (\Exception $exception) {
            Log::error(
                'Error deleting test result: '
                .$exception->getMessage()
            );

            return response()->json(
                ['message' => 'Failed to delete test result'],
                500,
            );
        }
    }
}
