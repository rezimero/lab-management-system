<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Tenant\EntitlementService;
use Illuminate\Http\JsonResponse;

class EntitlementController extends Controller
{
    public function __invoke(EntitlementService $entitlements): JsonResponse
    {
        $tenant = Tenant::current()
            ?? abort(422, 'Tenant context is required.');

        return response()->json([
            'data' => $entitlements->summary($tenant),
        ]);
    }
}
