<?php

namespace App\Http\Middleware;

use App\Models\ClinicianPortalAccount;
use App\Models\PatientPortalAccount;
use App\Models\PortalAccessToken;
use App\Services\Tenant\Portal\PortalAccessTokenService;
use App\Services\Tenant\Portal\PortalAuthenticationService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePortalAccessToken
{
    public function __construct(
        protected PortalAccessTokenService $tokens,
        protected PortalAuthenticationService $authentication,
    ) {
    }

    public function handle(
        Request $request,
        Closure $next,
        string $expectedAccountType
    ): Response {
        $plainTextToken = trim(
            (string) $request->bearerToken()
        );

        $token = $this->tokens->findUsable(
            $plainTextToken
        );

        if (
            ! $token
            || $token->account_type !== $expectedAccountType
        ) {
            return $this->unauthorized();
        }

        $account = $this->tokens->resolveAccount(
            $token
        );

        if (
            ! $this->accountCanAuthenticate(
                $account
            )
        ) {
            return $this->unauthorized();
        }

        $this->tokens->touchLastUsed($token);

        $request->attributes->set(
            'portal_access_token',
            $token
        );
        $request->attributes->set(
            'portal_account',
            $account
        );
        $request->attributes->set(
            'portal_account_type',
            $token->account_type
        );

        return $next($request);
    }

    private function accountCanAuthenticate(
        mixed $account
    ): bool {
        if ($account instanceof PatientPortalAccount) {
            return $this->authentication
                ->patientAccountCanAuthenticate(
                    $account
                );
        }

        if ($account instanceof ClinicianPortalAccount) {
            return $this->authentication
                ->clinicianAccountCanAuthenticate(
                    $account
                );
        }

        return false;
    }

    private function unauthorized(): JsonResponse
    {
        return response()->json([
            'message' => 'Unauthenticated.',
        ], 401);
    }
}
