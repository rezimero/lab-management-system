<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ActivatePortalInvitationRequest;
use App\Http\Requests\Portal\InspectPortalInvitationRequest;
use App\Services\Tenant\Portal\PortalInvitationService;
use Illuminate\Http\JsonResponse;

class PortalInvitationActivationController extends Controller
{
    public function __construct(
        private readonly PortalInvitationService $invitations
    ) {}

    public function inspect(
        InspectPortalInvitationRequest $request
    ): JsonResponse {
        return response()->json(
            $this->invitations->inspect(
                (string) $request->validated('token')
            )
        );
    }

    public function activate(
        ActivatePortalInvitationRequest $request
    ): JsonResponse {
        return response()->json(
            $this->invitations->activate(
                (string) $request->validated('token'),
                (string) $request->validated('password')
            )
        );
    }
}
