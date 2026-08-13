<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\PatientPortalLoginRequest;
use App\Models\PatientPortalAccount;
use App\Services\Tenant\Portal\PortalAuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientPortalAuthController extends Controller
{
    public function __construct(
        protected PortalAuthenticationService $authentication,
    ) {
    }

    public function login(
        PatientPortalLoginRequest $request
    ): JsonResponse {
        return response()->json(
            $this->authentication->loginPatient(
                $request->safe()->only([
                    'email',
                    'password',
                ])
            )
        );
    }

    public function me(
        Request $request
    ): JsonResponse {
        $account = $request->attributes->get(
            'portal_account'
        );

        if (! $account instanceof PatientPortalAccount) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json(
            $this->authentication
                ->patientContext($account)
        );
    }

    public function logout(
        Request $request
    ): JsonResponse {
        $this->authentication->logout($request);

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
