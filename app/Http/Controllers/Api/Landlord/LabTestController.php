<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Models\LabTest;
use App\Services\Landlord\Resource\LandlordTenantResourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LabTestController extends Controller
{
    private const SEARCH_FIELDS = [
        'name',
        'category',
    ];

    public function __construct(
        protected LandlordTenantResourceService $resources,
    ) {
    }

    /**
     * List all lab tests across all tenants (God Mode).
     */
    public function index(Request $request)
    {
        try {
            return response()->json(
                $this->resources->paginate(
                    modelClass: LabTest::class,
                    responseKey: 'lab_tests',
                    resourceLabel: 'lab tests',
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
                'Error fetching all lab tests: '
                .$exception->getMessage()
            );

            return response()->json(
                ['message' => 'Failed to fetch lab tests'],
                500,
            );
        }
    }

    /**
     * Get a single lab test from any tenant (God Mode).
     */
    public function show($id)
    {
        try {
            $labTest = $this->resources->find(
                LabTest::class,
                $id,
            );

            if ($labTest === null) {
                return response()->json(
                    ['message' => 'Lab test not found'],
                    404,
                );
            }

            return response()->json($labTest);
        } catch (\Exception $exception) {
            Log::error(
                'Error finding lab test: '
                .$exception->getMessage()
            );

            return response()->json(
                ['message' => 'Failed to find lab test'],
                500,
            );
        }
    }

    /**
     * Delete a lab test from any tenant (God Mode).
     */
    public function destroy($id)
    {
        try {
            $tenant = $this->resources->delete(
                LabTest::class,
                $id,
            );

            if ($tenant === null) {
                return response()->json(
                    ['message' => 'Lab test not found'],
                    404,
                );
            }

            return response()->json([
                'message' => 'Lab test deleted successfully',
                'tenant' => $tenant,
            ]);
        } catch (\Exception $exception) {
            Log::error(
                'Error deleting lab test: '
                .$exception->getMessage()
            );

            return response()->json(
                ['message' => 'Failed to delete lab test'],
                500,
            );
        }
    }
}
