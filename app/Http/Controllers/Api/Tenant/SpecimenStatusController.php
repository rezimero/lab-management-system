<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Specimen\UpdateSpecimenStatusRequest;
use App\Http\Resources\SpecimenResource;
use App\Services\Tenant\Specimen\SpecimenStatusService;
use Illuminate\Http\JsonResponse;

class SpecimenStatusController extends Controller
{
    public function __construct(
        protected SpecimenStatusService $statusService
    ) {
    }

    /**
     * Receive or reject a specimen.
     */
    public function update(
        UpdateSpecimenStatusRequest $request,
        string $specimen
    ): JsonResponse {
        $updatedSpecimen = (
            $this->statusService->transition(
                $specimen,
                $request->validated()
            )
        );

        return (new SpecimenResource(
            $updatedSpecimen
        ))
            ->additional([
                'message' => (
                    'Specimen status updated successfully.'
                ),
            ])
            ->response();
    }
}