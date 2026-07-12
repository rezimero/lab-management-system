<?php

namespace App\Http\Controllers\Api\Landlord;

use App\Http\Controllers\Controller;
use App\Services\Landlord\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
    ) {
    }

    /**
     * Return global statistics across all tenants.
     */
    public function stats(): JsonResponse
    {
        try {
            return response()->json(
                $this->dashboardService->statistics()
            );
        } catch (Throwable $exception) {
            Log::error(
                'Failed to get global dashboard statistics.',
                [
                    'exception' => $exception->getMessage(),
                ]
            );

            return response()->json([
                'message' => 'Failed to get stats',
            ], 500);
        }
    }

    /**
     * Return summarized dashboard information.
     */
    public function overview(): JsonResponse
    {
        try {
            return response()->json(
                $this->dashboardService->overview()
            );
        } catch (Throwable $exception) {
            Log::error(
                'Failed to get dashboard overview.',
                [
                    'exception' => $exception->getMessage(),
                ]
            );

            return response()->json([
                'message' => 'Failed to get overview',
            ], 500);
        }
    }

    /**
     * Return recent activity across all tenants.
     */
    public function recentActivity(
        Request $request
    ): JsonResponse {
        try {
            $limit = (int) $request->input(
                'limit',
                20
            );

            return response()->json(
                $this->dashboardService
                    ->recentActivity($limit)
            );
        } catch (Throwable $exception) {
            Log::error(
                'Failed to get recent dashboard activity.',
                [
                    'exception' => $exception->getMessage(),
                ]
            );

            return response()->json([
                'message' => 'Failed to get activity',
            ], 500);
        }
    }
}