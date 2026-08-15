<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\CompletePortalPasswordResetRequest;
use App\Http\Requests\Portal\InspectPortalPasswordResetRequest;
use App\Http\Requests\Portal\RequestPortalPasswordResetRequest;
use App\Services\Tenant\Portal\PortalPasswordResetService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PortalPasswordResetController extends Controller
{
    public function __construct(
        private readonly PortalPasswordResetService $passwordResets
    ) {}

    public function request(
        RequestPortalPasswordResetRequest $request
    ): JsonResponse {
        $this->passwordResets->requestSelfService(
            (string) $request->validated('account_type'),
            (string) $request->validated('email')
        );

        return response()->json([
            'message' => (
                'If an eligible portal account exists, a one-time password '
                .'reset link will be sent.'
            ),
        ], Response::HTTP_ACCEPTED);
    }

    public function inspect(
        InspectPortalPasswordResetRequest $request
    ): JsonResponse {
        return response()->json(
            $this->passwordResets->inspect(
                (string) $request->validated('token')
            )
        );
    }

    public function complete(
        CompletePortalPasswordResetRequest $request
    ): JsonResponse {
        return response()->json(
            $this->passwordResets->complete(
                (string) $request->validated('token'),
                (string) $request->validated('password')
            )
        );
    }
}
