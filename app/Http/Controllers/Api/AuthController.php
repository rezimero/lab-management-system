<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Landlord\User as LandlordUser;
use App\Services\Authentication\AuthenticationContextService;
use App\Services\Authentication\LandlordAuthenticationService;
use App\Services\Authentication\RegistrationService;
use App\Services\Authentication\TenantAuthenticationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected LandlordAuthenticationService $landlordAuthentication,
        protected TenantAuthenticationService $tenantAuthentication,
        protected AuthenticationContextService $authenticationContext,
        protected RegistrationService $registrationService,
    ) {
    }

    /**
     * Authenticate either a landlord superadmin or a tenant user.
     *
     * Landlord accounts are checked first. A tenant domain is required only
     * when the submitted email does not belong to a landlord account.
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'domain'   => ['nullable', 'string', 'max:255'],
        ]);

        $credentials['email'] = strtolower(trim($credentials['email']));
        $credentials['domain'] = isset($credentials['domain'])
            ? strtolower(trim($credentials['domain']))
            : null;

        $isLandlordUser = LandlordUser::query()
            ->where('email', $credentials['email'])
            ->exists();

        if ($isLandlordUser) {
            return response()->json(
                $this->landlordAuthentication->login($credentials)
            );
        }

        if (blank($credentials['domain'])) {
            throw ValidationException::withMessages([
                'domain' => ['A laboratory domain is required for tenant login.'],
            ]);
        }

        return response()->json(
            $this->tenantAuthentication->login($credentials)
        );
    }

    /**
     * Revoke the current access token and clear authentication context.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authenticationContext->logout($request);

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Return the currently authenticated user and associated context.
     */
    public function user(Request $request): JsonResponse
    {
        return response()->json(
            $this->authenticationContext->user($request)
        );
    }

    /**
     * Return the current tenant context.
     */
    public function tenant(): JsonResponse
    {
        return response()->json(
            $this->authenticationContext->tenant()
        );
    }

    /**
     * Register a tenant and its first administrator.
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'email', 'max:255'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
            'tenant_name'   => ['required', 'string', 'max:255'],
            'tenant_domain' => ['required', 'string', 'max:255'],
        ]);

        $data['name'] = trim($data['name']);
        $data['email'] = strtolower(trim($data['email']));
        $data['tenant_name'] = trim($data['tenant_name']);
        $data['tenant_domain'] = strtolower(trim($data['tenant_domain']));

        $this->registrationService->validate($data);

        return response()->json(
            $this->registrationService->register($data),
            201
        );
    }
}
