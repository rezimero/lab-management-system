<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\Landlord\Resource\LandlordTenantResourceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PatientController extends Controller
{
    private const SEARCH_FIELDS = [
        'first_name',
        'last_name',
        'email',
        'medical_record_number',
    ];

    public function __construct(
        protected LandlordTenantResourceService $resources,
    ) {
    }

    /**
     * List all patients across all tenants (God Mode).
     */
    public function index(Request $request)
    {
        try {
            return response()->json(
                $this->resources->paginate(
                    modelClass: Patient::class,
                    responseKey: 'patients',
                    resourceLabel: 'patients',
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
                'Error fetching all patients: '
                .$exception->getMessage()
            );

            return response()->json(
                ['message' => 'Failed to fetch patients'],
                500,
            );
        }
    }

    /**
     * Get a single patient from any tenant (God Mode).
     */
    public function show($id)
    {
        try {
            $patient = $this->resources->find(
                Patient::class,
                $id,
            );

            if ($patient === null) {
                return response()->json(
                    ['message' => 'Patient not found'],
                    404,
                );
            }

            return response()->json($patient);
        } catch (\Exception $exception) {
            Log::error(
                'Error finding patient: '
                .$exception->getMessage()
            );

            return response()->json(
                ['message' => 'Failed to find patient'],
                500,
            );
        }
    }

    /**
     * Delete a patient from any tenant (God Mode).
     */
    public function destroy($id)
    {
        try {
            $tenant = $this->resources->delete(
                Patient::class,
                $id,
            );

            if ($tenant === null) {
                return response()->json(
                    ['message' => 'Patient not found'],
                    404,
                );
            }

            return response()->json([
                'message' => 'Patient deleted successfully',
                'tenant' => $tenant,
            ]);
        } catch (\Exception $exception) {
            Log::error(
                'Error deleting patient: '
                .$exception->getMessage()
            );

            return response()->json(
                ['message' => 'Failed to delete patient'],
                500,
            );
        }
    }
}
