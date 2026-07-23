<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\Landlord\TenantContextRunner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImpersonationController extends Controller
{
    public function __construct(
        protected TenantContextRunner $tenantContextRunner
    ) {
    }

    /**
     * Enter an active tenant as its first administrator or user.
     */
    public function impersonate(
        Request $request,
        $tenantId
    ): JsonResponse {
        try {
            $tenant = Tenant::query()->find($tenantId);

            if (! $tenant) {
                return response()->json([
                    'message' => 'Tenant not found',
                ], 404);
            }

            if (! $tenant->is_active) {
                return response()->json([
                    'message' => (
                        'Cannot impersonate a suspended tenant'
                    ),
                ], 422);
            }

            $impersonation =
                $this->tenantContextRunner->run(
                    $tenant,
                    function (): ?array {
                        $user = TenantUser::query()
                            ->where('role', 'admin')
                            ->first();

                        if (! $user) {
                            $user = TenantUser::query()
                                ->first();
                        }

                        if (! $user) {
                            return null;
                        }

                        return [
                            'user' => [
                                'id' => $user->getKey(),
                                'name' => $user->name,
                                'email' => $user->email,
                                'role' => $user->role,
                            ],
                            'token' => $user
                                ->createToken(
                                    'impersonation_token'
                                )
                                ->plainTextToken,
                        ];
                    }
                );

            if ($impersonation === null) {
                return response()->json([
                    'message' => (
                        'No users found in this tenant'
                    ),
                ], 404);
            }

            $user = $impersonation['user'];

            session([
                'impersonating' => true,
                'impersonated_tenant_id' => (
                    $tenant->getKey()
                ),
                'impersonated_tenant_name' => (
                    $tenant->name
                ),
                'impersonated_user_id' => (
                    $user['id']
                ),
                'impersonated_user_email' => (
                    $user['email']
                ),
            ]);

            return response()->json([
                'message' => (
                    'Impersonating tenant: '
                    .$tenant->name
                ),
                'tenant' => [
                    'id' => $tenant->getKey(),
                    'name' => $tenant->name,
                    'domain' => $tenant->domain,
                ],
                'user' => $user,
                'token' => $impersonation['token'],
                'is_impersonating' => true,
            ]);
        } catch (Throwable $exception) {
            Log::error(
                'Error impersonating tenant: '
                .$exception->getMessage()
            );

            return response()->json([
                'message' => (
                    'Failed to impersonate tenant'
                ),
            ], 500);
        }
    }

    /**
     * Clear the current impersonation session state.
     */
    public function stopImpersonating(
        Request $request
    ): JsonResponse {
        try {
            session()->forget([
                'impersonating',
                'impersonated_tenant_id',
                'impersonated_tenant_name',
                'impersonated_user_id',
                'impersonated_user_email',
            ]);

            return response()->json([
                'message' => 'Stopped impersonating',
            ]);
        } catch (Throwable $exception) {
            Log::error(
                'Error stopping impersonation: '
                .$exception->getMessage()
            );

            return response()->json([
                'message' => (
                    'Failed to stop impersonation'
                ),
            ], 500);
        }
    }

    /**
     * Return the current impersonation session state.
     */
    public function status(): JsonResponse
    {
        $impersonating = (bool) session(
            'impersonating',
            false
        );

        return response()->json([
            'is_impersonating' => $impersonating,
            'tenant' => $impersonating
                ? [
                    'id' => session(
                        'impersonated_tenant_id'
                    ),
                    'name' => session(
                        'impersonated_tenant_name'
                    ),
                ]
                : null,
            'user' => $impersonating
                ? [
                    'id' => session(
                        'impersonated_user_id'
                    ),
                    'email' => session(
                        'impersonated_user_email'
                    ),
                ]
                : null,
        ]);
    }
}
