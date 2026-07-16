<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Specimen\ReplaceSpecimenRequest;
use App\Http\Resources\SpecimenResource;
use App\Services\Tenant\Specimen\SpecimenReplacementService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class SpecimenReplacementController extends Controller
{
    public function __construct(
        protected SpecimenReplacementService $replacementService
    ) {
    }

    /**
     * Collect a replacement for a rejected specimen.
     */
    public function store(
        ReplaceSpecimenRequest $request,
        string $specimen
    ): JsonResponse {
        $authenticatedId = $request
            ->user()
            ?->getAuthIdentifier();

        $replacement = $this->replacementService
            ->replace(
                $specimen,
                $request->validated(),
                is_numeric($authenticatedId)
                    ? (int) $authenticatedId
                    : null
            );

        return (new SpecimenResource(
            $replacement
        ))
            ->additional([
                'message' => (
                    'Replacement specimen collected successfully.'
                ),
            ])
            ->response()
            ->setStatusCode(
                Response::HTTP_CREATED
            );
    }
}
