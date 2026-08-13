<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalTenantContextController extends Controller
{
    public function show(
        Request $request
    ): JsonResponse {
        $tenant = $request->attributes->get(
            'tenant'
        );

        if (! $tenant instanceof Tenant) {
            return response()->json([
                'message' => 'Tenant not found.',
            ], 404);
        }

        return response()->json([
            'domain' => $tenant->domain,
            'name' => $tenant->name,
        ]);
    }
}
